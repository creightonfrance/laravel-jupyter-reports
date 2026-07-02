<?php

namespace CreightonFrance\LaravelJupyterReports\Jobs;

use CreightonFrance\LaravelJupyterReports\Contracts\NotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionRequest;
use CreightonFrance\LaravelJupyterReports\Events\NotebookReportCompleted;
use CreightonFrance\LaravelJupyterReports\Events\NotebookReportFailed;
use CreightonFrance\LaravelJupyterReports\Models\NotebookReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteNotebookReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $notebookReportId,
        public readonly NotebookExecutionRequest $request,
    ) {}

    public function handle(NotebookExecutor $executor): void
    {
        $result = $executor->execute($this->request);

        $report = NotebookReport::query()->findOrFail($this->notebookReportId);

        $report->update([
            'status' => $result->status,
            'output_disk' => $result->outputDisk,
            'output_path' => $result->outputPath,
            'error' => $result->error,
            'duration_ms' => $result->durationMs,
        ]);

        if ($result->successful()) {
            NotebookReportCompleted::dispatch($report->id, $result);
        } else {
            NotebookReportFailed::dispatch($report->id, $result);
        }
    }
}
