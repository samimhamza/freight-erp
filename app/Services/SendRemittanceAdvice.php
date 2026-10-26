<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Mail\RemittanceAdviceMail;
use App\Models\RemittanceAdvice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendRemittanceAdvice
{
    public function send(RemittanceAdvice $remittanceAdvice): bool
    {
        $remittanceAdvice->loadMissing('masterBill');

        if (! $remittanceAdvice->canBeSent()) {
            throw ValidationException::withMessages([
                'email' => 'This remittance advice cannot be sent.',
            ]);
        }

        $email = config('company.remittance_advice_email')
            ?? config('company.remittance_invoice_email')
            ?? config('company.payable_invoice_email');

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'The destination email address is not configured (REMITTANCE_ADVICE_EMAIL).',
            ]);
        }

        $wasDraft = $remittanceAdvice->status === InvoiceStatus::DRAFT;

        if ($wasDraft) {
            $remittanceAdvice->update([
                'status' => InvoiceStatus::SENT,
                'sent_at' => now(),
            ]);
        } else {
            $remittanceAdvice->update([
                'sent_at' => now(),
            ]);
        }

        Mail::to($email)->send(new RemittanceAdviceMail($remittanceAdvice));

        return $wasDraft;
    }
}

