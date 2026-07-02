<?php

namespace CreightonFrance\LaravelJupyterReports\Data;

use CreightonFrance\LaravelJupyterReports\Enums\OutputFormat;

readonly class NotebookExecutionRequest
{
    /**
     * @param  string  $notebookPath  Path to the source .ipynb to execute.
     * @param  array<string, mixed>  $parameters  Papermill-style parameters injected into the notebook (e.g. tenant id, date range, output path).
     * @param  int  $timeoutSeconds  Hard timeout for the execution; required so a runaway notebook cannot hang a queue worker indefinitely.
     * @param  string  $outputDisk  Laravel filesystem disk the resulting file should be collected onto.
     * @param  string  $outputPath  Path within $outputDisk where the result should be stored.
     */
    public function __construct(
        public string $notebookPath,
        public array $parameters,
        public OutputFormat $outputFormat,
        public int $timeoutSeconds,
        public string $outputDisk,
        public string $outputPath,
    ) {}
}
