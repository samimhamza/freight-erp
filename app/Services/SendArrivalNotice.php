<?php

namespace App\Services;

use App\Mail\ArrivalNoticeMail;
use App\Models\HouseBill;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendArrivalNotice
{
    public function send(HouseBill $houseBill): void
    {
        $houseBill->loadMissing(['customer', 'masterBill']);

        if ($houseBill->masterBill?->ata === null) {
            throw ValidationException::withMessages([
                'ata' => 'The arrival notice cannot be sent because the Master Bill has no Actual Arrival Date (ATA).',
            ]);
        }

        if (now()->startOfDay()->gt($houseBill->masterBill->ata->copy()->startOfDay()->addDays(14))) {
            throw ValidationException::withMessages([
                'ata' => 'The arrival notice cannot be sent because more than 14 days have passed since the Actual Arrival Date (ATA).',
            ]);
        }

        if (! $houseBill->canSendArrivalNotice()) {
            throw ValidationException::withMessages([
                'email' => 'This arrival notice cannot be sent to the customer.',
            ]);
        }

        $email = $houseBill->customer?->email;

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'The customer does not have an email address.',
            ]);
        }

        Mail::to($email)->send(new ArrivalNoticeMail($houseBill));

        $houseBill->update([
            'arrival_notice_sent_at' => now(),
        ]);
    }
}
