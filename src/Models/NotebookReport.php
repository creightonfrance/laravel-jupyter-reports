<?php

namespace CreightonFrance\LaravelJupyterReports\Models;

use CreightonFrance\LaravelJupyterReports\Enums\ExecutionStatus;
use CreightonFrance\LaravelJupyterReports\Enums\OutputFormat;
use Illuminate\Database\Eloquent\Model;

class NotebookReport extends Model
{
    protected $table = 'notebook_reports';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'output_format' => OutputFormat::class,
            'parameters' => 'array',
        ];
    }
}
