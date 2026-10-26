<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\InvoicePdfData;
use App\Support\Pdf\PdfFilename;
use Spatie\LaravelPdf\PdfBuilder;

class InvoicePrintController extends Controller
{
    public function __invoke(Invoice $invoice): PdfBuilder
    {
        $this->authorize('print', $invoice);

        return DocumentPdf::make(
            'pdf.invoices.show',
            InvoicePdfData::for($invoice),
            PdfFilename::forInvoice($invoice),
        );
    }
}
