<?php

namespace CreightonFrance\LaravelJupyterReports\Events;

use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionResult;
use Illuminate\Foundation\Events\Dispatchable;

class NotebookReportCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $notebookReportId,
        public readonly NotebookExecutionResult $result,
    ) {}
}
