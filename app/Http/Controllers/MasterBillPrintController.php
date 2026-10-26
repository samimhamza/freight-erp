<?php

namespace App\Http\Controllers;

use App\Models\MasterBill;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use Spatie\LaravelPdf\PdfBuilder;

class MasterBillPrintController extends Controller
{
    public function __invoke(MasterBill $masterBill): PdfBuilder
    {
        $this->authorize('print', $masterBill);

        $masterBill->load([
            'vessel',
            'origin',
            'destination',
            'portOfDischarge',
            'houseBills.customer',
            'houseBills.containers.container',
        ]);

        $totals = [
            'containers' => $masterBill->houseBills->sum(fn ($hb) => $hb->containers->count()),
            'house_bills' => $masterBill->houseBills->count(),
            'packages' => $masterBill->houseBills->sum(fn ($hb) => $hb->containers->sum('packages')),
            'vehicles' => $masterBill->total_vehicles,
            'gross_weight' => $masterBill->houseBills->sum(fn ($hb) => $hb->containers->sum('gross_weight')),
            'volume' => $masterBill->total_volume,
        ];

        return DocumentPdf::make(
            'pdf.master_bills.show',
            [
                'masterBill' => $masterBill,
                'totals' => $totals,
                'title' => 'Master Bill '.$masterBill->ocean_bill_of_lading,
            ],
            PdfFilename::forMasterBill($masterBill),
        );
    }
}
