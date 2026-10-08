<?php

namespace CreightonFrance\LaravelJupyterReports\Contracts;

use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionRequest;
use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionResult;

interface NotebookExecutor
{
    public function execute(NotebookExecutionRequest $request): NotebookExecutionResult;
}
