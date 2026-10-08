<?php

namespace CreightonFrance\LaravelJupyterReports\Data;

use CreightonFrance\LaravelJupyterReports\Enums\ExecutionStatus;

readonly class NotebookExecutionResult
{
    public function __construct(
        public ExecutionStatus $status,
        public ?string $outputDisk = null,
        public ?string $outputPath = null,
        public ?string $executedNotebookPath = null,
        public ?string $logExcerpt = null,
        public ?int $durationMs = null,
        public ?string $error = null,
    ) {}

    public function successful(): bool
    {
        return $this->status === ExecutionStatus::Completed;
    }
}
