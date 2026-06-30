<?php

namespace Creighton France\LaravelJupyterReports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Creighton France\LaravelJupyterReports\LaravelJupyterReports
 */
class LaravelJupyterReports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Creighton France\LaravelJupyterReports\LaravelJupyterReports::class;
    }
}
