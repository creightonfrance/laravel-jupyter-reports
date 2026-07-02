# ADR 0001: Notebook Execution Strategy

## Status

Accepted

## Context

`laravel-jupyter-reports` executes Jupyter notebooks on behalf of a host Laravel application and produces report output. The following facts shape this decision:

- **Trust model:** notebooks are end-user/tenant-authored, not fixed developer templates checked into the app. They are not fully trusted content.
- **Usage pattern:** report generation is scheduled/batch — dispatched as a Laravel job and processed by a job/listener, not executed synchronously on a web request.
- **Output formats:** primarily CSV and TSV. In practice this means the notebook itself, parameterized via papermill-style injection (e.g. tenant id, date range, output path), writes a CSV/TSV file to a given path as part of its own code. The executor's job is to run the notebook with the right parameters and then collect that file — this is not `nbconvert`-style post-hoc conversion of a notebook into HTML/PDF.
- **File delivery:** SFTP/upload of the resulting file is intentionally not built into this package. Laravel's own filesystem layer (Flysystem disks, including SFTP adapters) already covers this; the package's responsibility ends at writing output to a configured `Storage` disk.
- **Deployability:** this package is built standalone and generic. It will eventually be installed into a separate Laravel application as a dependency, so nothing in its design should assume a specific consumer app's infrastructure.
- **CI** is tracked as a separate task and is out of scope for this ADR.

The core question: how should the package actually invoke a notebook to produce its output?

## Options Considered

### 1. Shell out to `papermill`/`nbconvert` on the host process ("Process" executor)

Run `papermill notebook.ipynb output.ipynb -p key value ...` (or `jupyter nbconvert --execute`) as a subprocess of whatever process handles the job — in Laravel terms, via `Illuminate\Support\Facades\Process`, the same way an app shells out to any other external tool (e.g. `wkhtmltopdf`, `ffmpeg`).

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

Run each notebook execution in its own short-lived container, isolated from the host's filesystem, environment, and (optionally) network, then discard the container.

**Pros:**
- Real isolation boundary appropriate for untrusted code — a malicious notebook is contained regardless of what it attempts.
- Maps cleanly onto a queue job (dispatch → run container → collect output → destroy) and scales horizontally by adding queue workers.

**Cons:**
- Requires a container runtime reachable from every queue worker, plus a maintained runner image (Python, papermill, and whatever the notebooks depend on) — a hard infrastructure dependency this package cannot assume for every installer.
- Materially more operational complexity than options 1 or 2, which isn't justified for v1 given the priority of shipping something that fits a plain Job/listener flow with minimal new infrastructure.

## Decision

Adopt the **Process executor** (option 1) as the sole executor for v1: shell out to `papermill`/`jupyter nbconvert --execute` via `Illuminate\Support\Facades\Process`, invoked from a Laravel queue job.

This is implemented behind a `NotebookExecutor` contract (`execute(NotebookExecutionRequest): NotebookExecutionResult`) rather than being hardwired into the job. The contract exists specifically so that a different executor — most likely the Docker sidecar from option 3 — can be substituted later via config, without changing any call sites, if the trust model or scale requirements change.

## Consequences

- **No isolation from notebook code in v1.** Because notebooks are tenant/end-user authored and the Process executor runs unsandboxed in the queue worker's own environment, a malicious or buggy notebook has the same filesystem, environment variable, and network access as the worker. This is an accepted risk for v1 in favor of simplicity and shipping speed — not a gap intended to be silently patched later. It should be revisited before this package is exposed to genuinely adversarial or lower-trust content in production.
- **Runtime dependency.** Every queue worker host needs `papermill`/`jupyter` and a Python environment available on `$PATH`. This is a real, if lightweight, infrastructure requirement and should be documented in the package's installation instructions.
- **Async by nature.** Report generation is dispatch-and-complete, not request/response — the public API is built around queuing a job and reacting to a completion event, not a synchronous "render and return" call.
- **Timeout is required, not optional, config.** A hung or runaway notebook subprocess must not be allowed to block a queue worker indefinitely; every execution has a mandatory timeout.
- **File delivery stops at the Storage disk.** The package writes its output to a configured Laravel filesystem disk (which may itself be SFTP-backed via Flysystem) and does nothing further; moving that file anywhere else is the consuming application's responsibility.
- **Swapping the executor later is a config change, not a rewrite.** If isolation becomes a requirement, a `DockerNotebookExecutor` implementing the same `NotebookExecutor` contract can be added and selected via `default_executor` config without touching the facade, fluent builder, or job classes.
