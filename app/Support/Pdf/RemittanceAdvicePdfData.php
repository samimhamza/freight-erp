<?php

namespace App\Support\Pdf;

use App\Models\RemittanceAdvice;

class RemittanceAdvicePdfData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(RemittanceAdvice $remittanceAdvice): array
    {
        $remittanceAdvice->loadMissing([
            'carrier',
            'masterBill.carrier',
            'masterBill.vessel',
            'masterBill.origin',
            'masterBill.destination',
            'masterBill.portOfDischarge',
            'items.charge',
            'payments',
        ]);

        $subtotal = 0;
        $vatTotal = 0;
        $lines = [];

        foreach ($remittanceAdvice->items as $item) {
            $lineSubtotal = (float) $item->quantity * (float) $item->amount;
            $lineVat = (float) $item->vat;
            $lineTotal = $lineSubtotal + $lineVat;

            $subtotal += $lineSubtotal;
            $vatTotal += $lineVat;

            $lines[] = [
                'description' => $item->charge->name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->amount,
                'vat' => $lineVat,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'remittanceAdvice' => $remittanceAdvice,
            'remittanceInvoice' => $remittanceAdvice,
            'payableInvoice' => $remittanceAdvice,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'vatTotal' => $vatTotal,
            'grand_total' => $subtotal + $vatTotal,
            'total_paid' => (float) $remittanceAdvice->total_paid,
            'total_remaining' => (float) $remittanceAdvice->total_remaining,
            'title' => 'Remittance Advice '.$remittanceAdvice->advice_number,
        ];
    }
}

