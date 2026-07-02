<?php

namespace CreightonFrance\LaravelJupyterReports;

use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionRequest;
use CreightonFrance\LaravelJupyterReports\Enums\ExecutionStatus;
use CreightonFrance\LaravelJupyterReports\Enums\OutputFormat;
use CreightonFrance\LaravelJupyterReports\Jobs\ExecuteNotebookReportJob;
use CreightonFrance\LaravelJupyterReports\Models\NotebookReport;
use Illuminate\Support\Str;

class PendingNotebookReport
{
    /** @var array<string, mixed> */
    protected array $parameters = [];

    protected OutputFormat $outputFormat = OutputFormat::Csv;

    protected ?int $timeoutSeconds = null;

    protected ?string $queue = null;

    public function __construct(protected string $notebookPath) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters): static
    {
        $this->parameters = [...$this->parameters, ...$parameters];

        return $this;
    }

    public function outputAs(OutputFormat $format): static
    {
        $this->outputFormat = $format;

        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->timeoutSeconds = $seconds;

        return $this;
    }

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function queue(): NotebookReport
    {
        $config = config('jupyter-reports');

        $disk = $config['output']['disk'];
        $path = rtrim($config['output']['path'], '/').'/'.Str::uuid().'.'.$this->outputFormat->value;

        $report = NotebookReport::query()->create([
            'status' => ExecutionStatus::Pending,
            'notebook' => $this->notebookPath,
            'parameters' => $this->parameters,
            'output_format' => $this->outputFormat,
            'output_disk' => $disk,
            'output_path' => null,
        ]);

        $request = new NotebookExecutionRequest(
            notebookPath: $this->notebookPath,
            parameters: $this->parameters,
            outputFormat: $this->outputFormat,
            timeoutSeconds: $this->timeoutSeconds ?? $config['timeout'],
            outputDisk: $disk,
            outputPath: $path,
        );

        ExecuteNotebookReportJob::dispatch($report->id, $request)
            ->onQueue($this->queue ?? $config['queue']);

        return $report;
    }
}
