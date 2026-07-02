<?php

namespace CreightonFrance\LaravelJupyterReports\Executors;

/**
 * Runs commands directly on the host queue worker via
 * Illuminate\Support\Facades\Process, with no isolation from the notebook's
 * code. Supported but explicitly not the default — see ADR 0001.
 */
class ProcessNotebookExecutor extends AbstractNotebookExecutor {}
