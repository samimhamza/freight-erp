<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendInvoiceToCustomer
{
    public function send(Invoice $invoice): bool
    {
        $invoice->loadMissing('customer');

        if (! $invoice->canBeSentToCustomer()) {
            throw ValidationException::withMessages([
                'email' => 'This invoice cannot be sent to the customer.',
            ]);
        }

        $email = $invoice->customer->email;

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'The customer does not have an email address.',
            ]);
        }

        $wasDraft = $invoice->status === InvoiceStatus::DRAFT;

        if ($wasDraft) {
            $invoice->update([
                'status' => InvoiceStatus::SENT,
                'sent_at' => now(),
            ]);
        } else {
            $invoice->update([
                'sent_at' => now(),
            ]);
        }

        Mail::to($email)->send(new InvoiceMail($invoice));

        return $wasDraft;
    }
}
