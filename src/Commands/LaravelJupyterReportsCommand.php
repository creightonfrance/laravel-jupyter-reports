<?php

namespace Creighton France\LaravelJupyterReports\Commands;

use Illuminate\Console\Command;

class LaravelJupyterReportsCommand extends Command
{
    public $signature = 'laravel-jupyter-reports';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
