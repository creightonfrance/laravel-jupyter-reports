<?php

namespace CreightonFrance\LaravelJupyterReports\Executors;

use CreightonFrance\LaravelJupyterReports\Executors\Support\NotebookCommandBuilder;

/**
 * Runs each command in its own ephemeral container, isolated from the queue
 * worker's filesystem, environment, and network. Default executor — see
 * ADR 0001.
 *
 * Deliberately does not `docker pull` before running: the runner image is
 * expected to already be present on the host as a deploy-time step, so a
 * per-job execution only ever pays container-creation cost, never
 * image-pull cost. See ADR 0001's "guarding against Docker becoming a
 * performance drain" section.
 */
class DockerNotebookExecutor extends AbstractNotebookExecutor
{
    public function __construct(
        NotebookCommandBuilder $commandBuilder,
        string $workingDirectory,
        protected string $dockerBinary,
        protected string $image,
        protected string $network,
        protected string $memory,
        protected string $cpus,
        protected int $pidsLimit,
    ) {
        parent::__construct($commandBuilder, $workingDirectory);
    }

    /**
     * @param  list<string>  $command
     * @return list<string>
     */
    protected function wrapCommand(array $command): array
    {
        // Only the scratch working directory is mounted. Notebook source
        // paths must live under it for the container to see them - a real
        // implementation would copy the notebook in here before running.
        return [
            $this->dockerBinary, 'run', '--rm',
            '--network', $this->network,
            '--memory', $this->memory,
            '--cpus', $this->cpus,
            '--pids-limit', (string) $this->pidsLimit,
            '-v', "{$this->workingDirectory}:{$this->workingDirectory}",
            $this->image,
            ...$command,
        ];
    }
}
