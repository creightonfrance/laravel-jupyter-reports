<?php

namespace CreightonFrance\LaravelJupyterReports;

class LaravelJupyterReports
{
    public function report(string $notebookPath): PendingNotebookReport
    {
        return new PendingNotebookReport($notebookPath);
    }
}
