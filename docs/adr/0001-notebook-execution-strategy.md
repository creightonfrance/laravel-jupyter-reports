# ADR 0001: Notebook Execution Strategy

## Status

Accepted

## Context

`laravel-jupyter-reports` executes Jupyter notebooks on behalf of a host Laravel application and produces report output. The following facts shape this decision:

- **Trust model:** notebooks are end-user/tenant-authored, not fixed developer templates checked into the app. They are not fully trusted content.
- **Usage pattern:** report generation is scheduled/batch — dispatched as a Laravel job and processed by a job/listener, not executed synchronously on a web request.
- **Output formats:** two distinct execution styles need to be supported:
  - **Data export** (primary case): the notebook itself, parameterized via papermill-style injection (e.g. tenant id, date range, output path), writes a CSV/TSV file to a given path as part of its own code. The executor's job is to run the notebook with the right parameters and then collect that file.
  - **Document conversion**: `nbconvert`-style export of the executed notebook into other formats (HTML, PDF, script, markdown). This is a genuinely different operation from data export — it converts the notebook document itself rather than collecting a file the notebook wrote — so the execution strategy needs to accommodate both, not just the CSV/TSV case.
- **Output storage:** the resulting artifact can be persisted either directly in the database or via a Laravel `Storage` disk (local, S3, SFTP, etc.) — and these are not two different mechanisms from the package's point of view. Laravel's `Storage` facade is a thin wrapper around Flysystem adapters; there's no built-in "database" driver, but `Storage::extend()` lets any app register a custom Flysystem adapter that reads/writes blobs to a DB table/column. Once registered, `Storage::disk('database')->put(...)` behaves identically to `Storage::disk('s3')->put(...)`. So the package targets the `Storage` disk abstraction uniformly; "store in DB" is just a disk choice, not a separate code path. DB storage is the simplest option (no extra infra, fine for small CSV/TSV outputs); real Storage disks matter for larger files or when a tenant needs the file to live somewhere addressable (e.g. SFTP).
- **File delivery:** SFTP/upload of the resulting file beyond writing to a disk is intentionally not built into this package — Laravel's own filesystem layer already covers it.
- **Deployability:** this package is built standalone and generic. It will eventually be installed into a separate Laravel application as a dependency, so nothing in its design should assume a specific consumer app's infrastructure.

The core question: how should the package actually invoke a notebook to produce its output?

## Options Considered

### 1. Shell out to `papermill`/`nbconvert` on the host process ("Process" executor)

Run `papermill notebook.ipynb output.ipynb -p key value ...` for data-export notebooks, or `jupyter nbconvert --execute --to <format>` for document conversion, as a subprocess of whatever process handles the job — in Laravel terms, via `Illuminate\Support\Facades\Process`, the same way an app shells out to any other external tool (e.g. `wkhtmltopdf`, `ffmpeg`).

**Pros:**
- Simplest possible implementation and operational model — no new infrastructure to stand up.
- The app-level flow is exactly "dispatch a job, a Job/listener processes it," with no container orchestration in between.
- Fastest to ship; only requires `papermill`/`jupyter`/Python on the queue worker's `$PATH`.

**Cons:**
- No isolation: the notebook's Python code runs with the same filesystem, environment variables, and network access as the queue worker itself. A malicious or buggy notebook is not sandboxed from the host.
- Ties the queue worker's runtime environment to having a working Python/Jupyter install alongside PHP.

### 2. Jupyter Kernel Gateway (or Enterprise Gateway)

Run a long-lived Kernel Gateway service that accepts execution requests over HTTP and manages a pool of kernels; the Laravel app talks to it as a remote service instead of shelling out locally.

**Pros:**
- Kernels can be kept warm, avoiding cold-start cost per execution.
- Decouples notebook execution from the PHP/queue-worker process entirely.

**Cons:**
- Requires operating an always-on Python service as permanent infrastructure — a standing service every installer of this package would need to run and secure.
- Warm-kernel latency benefits don't matter here: usage is batch/scheduled, not latency-sensitive.
- Isolation is no better than the Process option by default — plain Kernel Gateway runs kernels as OS processes on the same host/container, sharing filesystem and network unless additional per-kernel sandboxing is layered on top (at which point this collapses into option 3 with extra moving parts).

### 3. Docker sidecar (ephemeral container per execution)

Run each notebook execution — whether a papermill data-export run or an nbconvert document conversion — in its own short-lived container, isolated from the host's filesystem, environment, and (optionally) network, then discard the container.

**Pros:**
- Real isolation boundary appropriate for untrusted code — a malicious notebook is contained regardless of what it attempts.
- Maps cleanly onto a queue job (dispatch → run container → collect output → destroy) and scales horizontally by adding queue workers.
- Same container image can host both `papermill` and `nbconvert`, so it covers both execution styles without extra infrastructure per style.

**Cons:**
- Requires a container runtime reachable from every queue worker, plus a maintained runner image (Python, papermill, nbconvert, and whatever the notebooks depend on) — a real infrastructure dependency this package cannot assume is already present for every installer.
- Per-job container startup adds overhead compared to a bare subprocess — needs to be actively managed (see Decision) rather than accepted blindly.

## Decision

Adopt the **Docker sidecar** (option 3) as the **default** executor: each notebook execution — papermill data-export or nbconvert document conversion — runs in its own ephemeral container, isolated from the queue worker's filesystem, environment, and network by default.

The **Process executor** (option 1) is retained and fully supported as a **non-default, explicitly-selected** alternative — for local development, CI-like environments, or deployments that have already established the notebooks they run are trusted and want to avoid the container runtime dependency. Kernel Gateway (option 2) remains rejected/deferred for the reasons above.

Both executors implement the same `NotebookExecutor` contract (`execute(NotebookExecutionRequest): NotebookExecutionResult`), selected via config (`default_executor`), so choosing Process over Docker — or the reverse — is a config change, not a rewrite of call sites.

**Guarding against Docker becoming a performance drain:** since usage is batch/scheduled rather than latency-sensitive, per-job container overhead is acceptable in principle, but it must stay small and predictable rather than becoming a real cost as volume grows:
- Ship a single, purpose-built, minimal runner image (Python + papermill + nbconvert + only the libraries notebooks actually need) rather than a general-purpose multi-gigabyte Jupyter image. Image size drives both pull time and per-container startup time.
- The runner image is pulled once per host as part of deployment/provisioning, not per job — `docker run` against an already-present image only pays container-creation cost (low, roughly constant), never image-pull cost.
- No warm-container pooling in v1 (that would blur the isolation boundary between jobs). If per-job container startup overhead is later measured to matter at scale, that's a targeted follow-up, not something to design around speculatively now.

**Storage:** output is written through the `Storage` disk abstraction regardless of executor, per the Context above — a DB-backed disk and a filesystem/S3/SFTP-backed disk are interchangeable from the package's perspective; the choice is a config value (`output.disk`), not a fork in the code.

## Consequences

- **Isolation is the default, not an opt-in.** Because notebooks are tenant/end-user authored, the default execution path (Docker) contains a malicious or buggy notebook to its own container — no host filesystem, environment variable, or network access beyond what's explicitly granted.
- **Container runtime is now a real infrastructure dependency for the default path.** Every queue worker host needs a reachable container runtime and the runner image available. This is heavier than the Process-only design previously considered, and is accepted because isolation is required given the trust model.
- **Process remains available, deliberately not the default.** Consumers can opt into it via config when they've made a conscious call that isolation isn't needed (e.g. trusted internal notebooks, local dev) — the risk of running unsandboxed code is then a decision the consuming app makes explicitly, not an inherited default.
- **Both data-export and document-conversion flows are first-class.** The executor and its request/result shapes need to support papermill-style parameterized CSV/TSV output and nbconvert-style format conversion, not just one of the two.
- **Storage is disk-agnostic.** Whether output ends up as a DB blob or a file on S3/SFTP/local disk is a `Storage` disk configuration choice; the package's read/write path is identical either way.
- **Async by nature.** Report generation is dispatch-and-complete, not request/response — the public API is built around queuing a job and reacting to a completion event, not a synchronous "render and return" call.
- **Timeout is required, not optional, config.** A hung or runaway execution — container or subprocess — must not be allowed to block a queue worker indefinitely.
- **Per-job container overhead must be actively kept small.** A minimal runner image, pre-pulled at deploy time rather than per job, keeps Docker's default-path overhead low and roughly constant rather than a growing drain as report volume increases.
