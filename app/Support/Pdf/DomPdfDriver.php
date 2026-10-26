<?php

namespace App\Support\Pdf;

use Dompdf\Options;
use Spatie\LaravelPdf\Drivers\DomPdfDriver as SpatieDomPdfDriver;

class DomPdfDriver extends SpatieDomPdfDriver
{
    protected function buildOptions(): Options
    {
        $options = parent::buildOptions();

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $options->setTempDir($tempDir);

        return $options;
    }
}

