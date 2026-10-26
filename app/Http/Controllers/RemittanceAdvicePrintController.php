<?php

namespace App\Http\Controllers;

use App\Models\RemittanceAdvice;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use App\Support\Pdf\RemittanceAdvicePdfData;
use Spatie\LaravelPdf\PdfBuilder;

class RemittanceAdvicePrintController extends Controller
{
    public function __invoke(RemittanceAdvice $remittanceAdvice): PdfBuilder
    {
        $this->authorize('print', $remittanceAdvice);

        return DocumentPdf::make(
            'pdf.remittance_advices.show',
            RemittanceAdvicePdfData::for($remittanceAdvice),
            PdfFilename::forRemittanceAdvice($remittanceAdvice),
        );
    }
}

