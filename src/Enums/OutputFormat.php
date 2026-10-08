<?php

namespace CreightonFrance\LaravelJupyterReports\Enums;

enum OutputFormat: string
{
    case Csv = 'csv';
    case Tsv = 'tsv';
    case Html = 'html';
    case Pdf = 'pdf';
    case Script = 'script';
    case Markdown = 'markdown';

    /**
     * Csv/Tsv are data exports: the notebook itself writes the file to the
     * injected `output_path` parameter during the papermill run. Everything
     * else is a document conversion: nbconvert runs as a second step against
     * the already-executed notebook. See ADR 0001.
     */
    public function usesNbconvert(): bool
    {
        return match ($this) {
            self::Csv, self::Tsv => false,
            self::Html, self::Pdf, self::Script, self::Markdown => true,
        };
    }
}
