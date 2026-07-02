<?php

namespace CreightonFrance\LaravelJupyterReports\Enums;

enum ExecutorDriver: string
{
    case Docker = 'docker';
    case Process = 'process';
}
