<?php

namespace CreightonFrance\LaravelJupyterReports\Executors;

use CreightonFrance\LaravelJupyterReports\Contracts\NotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionRequest;
use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionResult;
use CreightonFrance\LaravelJupyterReports\Enums\ExecutionStatus;
use CreightonFrance\LaravelJupyterReports\Executors\Support\CommandOutcome;
use CreightonFrance\LaravelJupyterReports\Executors\Support\NotebookCommandBuilder;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 * Shared papermill -> (optional) nbconvert -> collect pipeline for every
 * NotebookExecutor driver. Concrete executors only need to say how a given
 * command actually runs (directly on the host, or wrapped in a container) by
 * overriding wrapCommand(). See ADR 0001.
 */
abstract class AbstractNotebookExecutor implements NotebookExecutor
{
    public function __construct(
        protected NotebookCommandBuilder $commandBuilder,
        protected string $workingDirectory,
    ) {}

    public function execute(NotebookExecutionRequest $request): NotebookExecutionResult
    {
        if (! is_dir($this->workingDirectory)) {
            mkdir($this->workingDirectory, recursive: true);
        }

        $executedNotebookPath = "{$this->workingDirectory}/".uniqid('executed_', true).'.ipynb';
        $localOutputPath = "{$this->workingDirectory}/".uniqid('output_', true).'.'.$request->outputFormat->value;

        $startedAt = microtime(true);

        $outcome = $this->run(
            $this->commandBuilder->papermillCommand($request, $executedNotebookPath, $localOutputPath),
            $request->timeoutSeconds,
        );

        if (! $outcome->successful) {
            return $this->failure($outcome, 'papermill', $executedNotebookPath, $startedAt);
        }

        if ($request->outputFormat->usesNbconvert()) {
            $outcome = $this->run(
                $this->commandBuilder->nbconvertCommand($request, $executedNotebookPath, $localOutputPath),
                $request->timeoutSeconds,
            );

            if (! $outcome->successful) {
                return $this->failure($outcome, 'nbconvert', $executedNotebookPath, $startedAt);
            }
        }

        if (! is_file($localOutputPath)) {
            return new NotebookExecutionResult(
                status: ExecutionStatus::Failed,
                executedNotebookPath: $executedNotebookPath,
                durationMs: $this->elapsedMs($startedAt),
                error: "Execution completed but no output file was found at {$localOutputPath}.",
            );
        }

        Storage::disk($request->outputDisk)->put($request->outputPath, file_get_contents($localOutputPath));
        @unlink($localOutputPath);

        return new NotebookExecutionResult(
            status: ExecutionStatus::Completed,
            outputDisk: $request->outputDisk,
            outputPath: $request->outputPath,
            executedNotebookPath: $executedNotebookPath,
            durationMs: $this->elapsedMs($startedAt),
        );
    }

    /**
     * Subclasses override this to run the command somewhere other than
     * directly on the host — e.g. wrapped in `docker run`. Identity by
     * default.
     *
     * @param  list<string>  $command
     * @return list<string>
     */
    protected function wrapCommand(array $command): array
    {
        return $command;
    }

    /**
     * @param  list<string>  $command
     */
    protected function run(array $command, int $timeoutSeconds): CommandOutcome
    {
        try {
            $result = Process::timeout($timeoutSeconds)->run($this->wrapCommand($command));
        } catch (ProcessTimedOutException) {
            return CommandOutcome::timedOut();
        }

        return CommandOutcome::fromProcessResult($result);
    }

    protected function failure(CommandOutcome $outcome, string $step, string $executedNotebookPath, float $startedAt): NotebookExecutionResult
    {
        return new NotebookExecutionResult(
            status: $outcome->timedOut ? ExecutionStatus::TimedOut : ExecutionStatus::Failed,
            executedNotebookPath: is_file($executedNotebookPath) ? $executedNotebookPath : null,
            logExcerpt: $outcome->errorOutput,
            durationMs: $this->elapsedMs($startedAt),
            error: $outcome->timedOut
                ? "Notebook execution ({$step}) exceeded its configured timeout."
                : "{$step} exited with status {$outcome->exitCode}.",
        );
    }

    protected function elapsedMs(float $startedAt): int
    {
        return (int) ((microtime(true) - $startedAt) * 1000);
    }
}
