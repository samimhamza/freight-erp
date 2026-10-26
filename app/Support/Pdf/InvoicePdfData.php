<?php

namespace App\Support\Pdf;

use App\Models\Invoice;

class InvoicePdfData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'customer.country',
            'houseBill.masterBill.vessel',
            'houseBill.containers.container',
            'items.charge',
            'payments',
        ]);

        $subtotal = 0;
        $vatTotal = 0;
        $lines = [];

        foreach ($invoice->items as $item) {
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
            'invoice' => $invoice,
            'house_bill' => $invoice->house_bill_number,
            'container_numbers' => $invoice->all_container_numbers,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'grand_total' => $subtotal + $vatTotal,
            'total_paid' => (float) $invoice->total_paid,
            'total_remaining' => (float) $invoice->total_remaining,
            'title' => 'Invoice '.$invoice->invoice_number,
        ];
    }
}
