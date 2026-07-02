<?php

namespace CreightonFrance\LaravelJupyterReports;

use CreightonFrance\LaravelJupyterReports\Commands\LaravelJupyterReportsCommand;
use CreightonFrance\LaravelJupyterReports\Contracts\NotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Enums\ExecutorDriver;
use CreightonFrance\LaravelJupyterReports\Executors\DockerNotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Executors\ProcessNotebookExecutor;
use CreightonFrance\LaravelJupyterReports\Executors\Support\NotebookCommandBuilder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelJupyterReportsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-jupyter-reports')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_notebook_reports_table')
            ->hasCommand(LaravelJupyterReportsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(NotebookExecutor::class, function ($app) {
            $config = $app['config']['jupyter-reports'];
            $driverConfig = $config['executors'][$config['default_executor']];

            $commandBuilder = new NotebookCommandBuilder(
                papermillBinary: $config['binaries']['papermill'],
                jupyterBinary: $config['binaries']['jupyter'],
            );

            return match (ExecutorDriver::from($config['default_executor'])) {
                ExecutorDriver::Docker => new DockerNotebookExecutor(
                    commandBuilder: $commandBuilder,
                    workingDirectory: $config['working_directory'],
                    dockerBinary: $driverConfig['binary'],
                    image: $driverConfig['image'],
                    network: $driverConfig['network'],
                    memory: $driverConfig['memory'],
                    cpus: $driverConfig['cpus'],
                    pidsLimit: $driverConfig['pids_limit'],
                ),
                ExecutorDriver::Process => new ProcessNotebookExecutor(
                    commandBuilder: $commandBuilder,
                    workingDirectory: $config['working_directory'],
                ),
            };
        });
    }
}
