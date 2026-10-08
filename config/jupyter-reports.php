<?php

// config for CreightonFrance/LaravelJupyterReports
use CreightonFrance\LaravelJupyterReports\Executors\DockerNotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Executors\ProcessNotebookExecutor;

return [

    // Docker is the default: notebooks are tenant-authored and not fully
    // trusted, so isolation is the default rather than an opt-in. Process
    // is fully supported but must be selected explicitly. See ADR 0001.
    'default_executor' => env('JUPYTER_REPORTS_EXECUTOR', 'docker'),

    // Commands invoked either directly (Process) or inside the container
    // (Docker) - same binary names either way, since the runner image ships
    // the same tools as a local dev install would.
    'binaries' => [
        'papermill' => env('JUPYTER_REPORTS_PAPERMILL_BIN', 'papermill'),
        'jupyter' => env('JUPYTER_REPORTS_JUPYTER_BIN', 'jupyter'),
    ],

    'executors' => [
        'docker' => [
            'driver' => DockerNotebookExecutor::class,
            'binary' => env('JUPYTER_REPORTS_DOCKER_BIN', 'docker'),
            // Must already be pulled on the host as a deploy-time step -
            // this package never pulls it per job. See ADR 0001.
            'image' => env('JUPYTER_REPORTS_DOCKER_IMAGE', 'creightonfrance/jupyter-reports-runner:latest'),
            'network' => env('JUPYTER_REPORTS_DOCKER_NETWORK', 'none'),
            'memory' => env('JUPYTER_REPORTS_DOCKER_MEMORY', '512m'),
            'cpus' => env('JUPYTER_REPORTS_DOCKER_CPUS', '1.0'),
            'pids_limit' => (int) env('JUPYTER_REPORTS_DOCKER_PIDS_LIMIT', 128),
        ],
        'process' => [
            'driver' => ProcessNotebookExecutor::class,
        ],
    ],

    // Required, not optional: a hung notebook must not be able to block a
    // queue worker (or container) indefinitely. Overridable per-job via
    // PendingNotebookReport::timeout().
    'timeout' => (int) env('JUPYTER_REPORTS_TIMEOUT', 120),

    // Host-side scratch directory used to exchange files with the notebook
    // process/container.
    'working_directory' => storage_path('app/jupyter-reports'),

    'output' => [
        // csv/tsv are data exports the notebook writes itself; the rest are
        // nbconvert document conversions of the executed notebook. See
        // ADR 0001.
        'formats' => ['csv', 'tsv', 'html', 'pdf', 'script', 'markdown'],
        // Any Laravel filesystem disk - including a custom one backed by DB
        // columns via Storage::extend(). Storage is disk-agnostic from this
        // package's point of view; see ADR 0001.
        'disk' => env('JUPYTER_REPORTS_DISK', 'local'),
        'path' => 'jupyter-reports',
    ],

    'queue' => env('JUPYTER_REPORTS_QUEUE', 'default'),

];
