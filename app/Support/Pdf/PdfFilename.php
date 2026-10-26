<?php

namespace App\Support\Pdf;

use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\MasterBill;
use App\Models\RemittanceAdvice;

class PdfFilename
{
    public static function sanitize(string $value): string
    {
        $sanitized = preg_replace('/[\/\\\\:*?"<>|]+/', '-', trim($value));
        $sanitized = preg_replace('/\s+/', '_', $sanitized ?? '');
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        $sanitized = trim($sanitized, '_');

        return $sanitized !== '' ? $sanitized : 'document';
    }

    public static function forInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing('customer');

        return self::sanitize($invoice->invoice_number.'_'.$invoice->customer->name);
    }

    public static function forRemittanceAdvice(RemittanceAdvice $remittanceAdvice): string
    {
        $remittanceAdvice->loadMissing(['carrier', 'masterBill.carrier']);

        $carrier = $remittanceAdvice->carrier?->name ?? $remittanceAdvice->masterBill?->carrier?->name ?? 'carrier';

        return self::sanitize($remittanceAdvice->advice_number.'_'.$carrier);
    }

    public static function forRemittanceInvoice(RemittanceAdvice $remittanceInvoice): string
    {
        return self::forRemittanceAdvice($remittanceInvoice);
    }

    public static function forPayableInvoice(RemittanceAdvice $payableInvoice): string
    {
        return self::forRemittanceAdvice($payableInvoice);
    }

    public static function forMasterBill(MasterBill $masterBill): string
    {
        return self::sanitize((string) ($masterBill->console ?? $masterBill->ocean_bill_of_lading ?? 'master-bill'));
    }

    public static function forHouseBill(HouseBill $houseBill): string
    {
        return self::sanitize((string) $houseBill->house_bill);
    }

    public static function forArrivalNotice(HouseBill $houseBill): string
    {
        $houseBill->loadMissing('customer');

        $customer = $houseBill->customer?->name ?? 'customer';

        return self::sanitize('Arrival_Notice_'.$houseBill->house_bill.'_'.$customer);
    }
}
