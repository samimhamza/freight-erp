<?php

namespace App\Support\Pdf;

use App\Models\HouseBill;

class ArrivalNoticePdfData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(HouseBill $houseBill): array
    {
        $houseBill->loadMissing([
            'masterBill.vessel',
            'masterBill.carrier',
            'masterBill.origin',
            'masterBill.portOfDischarge',
            'masterBill.destination',
            'consignor.country',
            'customer.country',
            'notifyParty.country',
            'containers.container',
            'containers.vehicles',
        ]);

        $containers = $houseBill->containers;

        $totals = [
            'containers' => $containers->count(),
            'packages' => (int) $containers->sum('packages'),
            'vehicles' => (int) $containers->sum(fn ($row) => $row->vehicles->count()),
            'gross_weight' => (float) $containers->sum('gross_weight'),
            'net_weight' => (float) $containers->sum('net_weight'),
            'volume' => (float) $containers->sum(fn ($row) => (float) ($row->container?->volume ?? 0)),
        ];

        return [
            'houseBill' => $houseBill,
            'masterBill' => $houseBill->masterBill,
            'customer' => $houseBill->customer,
            'consignor' => $houseBill->consignor,
            'notifyParty' => $houseBill->notifyParty,
            'containers' => $containers,
            'totals' => $totals,
            'noticeDate' => now(),
            'title' => 'Arrival Notice - '.$houseBill->house_bill,
        ];
    }
}
