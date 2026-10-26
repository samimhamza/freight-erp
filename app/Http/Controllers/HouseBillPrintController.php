<?php

namespace App\Http\Controllers;

use App\Models\HouseBill;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use Spatie\LaravelPdf\PdfBuilder;

class HouseBillPrintController extends Controller
{
    public function __invoke(HouseBill $houseBill): PdfBuilder
    {
        $this->authorize('print', $houseBill);

        $houseBill->load([
            'masterBill.vessel',
            'masterBill.origin',
            'masterBill.destination',
            'masterBill.portOfDischarge',
            'consignor.country',
            'customer.country',
            'notifyParty',
            'containers.container',
            'containers.vehicles',
        ]);

        $totals = [
            'containers' => $houseBill->containers->count(),
            'packages' => $houseBill->containers->sum('packages'),
            'vehicles' => $houseBill->containers->sum(fn ($row) => $row->vehicles->count()),
            'gross_weight' => $houseBill->containers->sum('gross_weight'),
            'volume' => $houseBill->containers->sum(fn ($row) => (float) $row->container->volume),
        ];

        return DocumentPdf::make(
            'pdf.house_bills.show',
            [
                'houseBill' => $houseBill,
                'totals' => $totals,
                'title' => 'House Bill '.$houseBill->house_bill,
            ],
            PdfFilename::forHouseBill($houseBill),
        );
    }
}
