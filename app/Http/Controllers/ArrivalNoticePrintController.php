<?php

namespace App\Http\Controllers;

use App\Models\HouseBill;
use App\Support\Pdf\ArrivalNoticePdfData;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use Spatie\LaravelPdf\PdfBuilder;

class ArrivalNoticePrintController extends Controller
{
    public function __invoke(HouseBill $houseBill): PdfBuilder
    {
        $this->authorize('arrivalNotice', $houseBill);
        abort_unless($houseBill->canPrintArrivalNotice(), 404, 'Arrival notice cannot be generated because the Actual Arrival Date (ATA) has not been set.');

        return DocumentPdf::make(
            'pdf.arrival_notices.show',
            ArrivalNoticePdfData::for($houseBill),
            PdfFilename::forArrivalNotice($houseBill),
        );
    }
}
