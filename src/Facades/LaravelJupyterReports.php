<?php

namespace CreightonFrance\LaravelJupyterReports\Facades;

use CreightonFrance\LaravelJupyterReports\PendingNotebookReport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingNotebookReport report(string $notebookPath)
 *
 * @see \CreightonFrance\LaravelJupyterReports\LaravelJupyterReports
 */
class LaravelJupyterReports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CreightonFrance\LaravelJupyterReports\LaravelJupyterReports::class;
    }
}
