<?php

namespace CreightonFrance\LaravelJupyterReports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CreightonFrance\LaravelJupyterReports\LaravelJupyterReports
 */
class LaravelJupyterReports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CreightonFrance\LaravelJupyterReports\LaravelJupyterReports::class;
    }
}
