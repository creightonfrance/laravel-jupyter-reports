<?php

namespace CreightonFrance\LaravelJupyterReports\Executors\Support;

use Illuminate\Contracts\Process\ProcessResult;

readonly class CommandOutcome
{
    public function __construct(
        public bool $successful,
        public bool $timedOut,
        public int $exitCode,
        public string $output,
        public string $errorOutput,
    ) {}

    public static function fromProcessResult(ProcessResult $result): self
    {
        return new self(
            successful: $result->successful(),
            timedOut: false,
            exitCode: $result->exitCode(),
            output: $result->output(),
            errorOutput: $result->errorOutput(),
        );
    }

    public static function timedOut(): self
    {
        return new self(successful: false, timedOut: true, exitCode: -1, output: '', errorOutput: '');
    }
}
