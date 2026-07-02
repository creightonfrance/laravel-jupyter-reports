<?php

namespace CreightonFrance\LaravelJupyterReports\Executors\Support;

use CreightonFrance\LaravelJupyterReports\Data\NotebookExecutionRequest;

/**
 * Builds the underlying papermill/nbconvert command lines shared by every
 * NotebookExecutor implementation, regardless of where that command actually
 * runs (directly on the host, or wrapped inside a container). See ADR 0001.
 */
readonly class NotebookCommandBuilder
{
    public function __construct(
        protected string $papermillBinary,
        protected string $jupyterBinary,
    ) {}

    /**
     * Always run first: executes the notebook with injected parameters. For
     * Csv/Tsv output, the notebook itself writes its result to the injected
     * `output_path` parameter, so this step alone produces the final file.
     *
     * @return list<string>
     */
    public function papermillCommand(NotebookExecutionRequest $request, string $executedNotebookPath, string $localOutputPath): array
    {
        $command = [$this->papermillBinary, $request->notebookPath, $executedNotebookPath];

        $parameters = $request->outputFormat->usesNbconvert()
            ? $request->parameters
            : [...$request->parameters, 'output_path' => $localOutputPath];

        foreach ($parameters as $key => $value) {
            $command[] = '-p';
            $command[] = (string) $key;
            $command[] = (string) $value;
        }

        return $command;
    }

    /**
     * Only run when the output format needs document conversion: converts
     * the already-executed notebook produced by papermillCommand() into the
     * target format (html/pdf/script/markdown).
     *
     * @return list<string>
     */
    public function nbconvertCommand(NotebookExecutionRequest $request, string $executedNotebookPath, string $localOutputPath): array
    {
        return [
            $this->jupyterBinary,
            'nbconvert',
            "--to={$request->outputFormat->value}",
            "--output={$localOutputPath}",
            $executedNotebookPath,
        ];
    }
}
