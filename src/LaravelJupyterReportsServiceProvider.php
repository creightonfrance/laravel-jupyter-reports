<?php

namespace CreightonFrance\LaravelJupyterReports;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CreightonFrance\LaravelJupyterReports\Commands\LaravelJupyterReportsCommand;

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
            ->hasMigration('create_laravel_jupyter_reports_table')
            ->hasCommand(LaravelJupyterReportsCommand::class);
    }
}
