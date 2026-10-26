<?php

namespace App\Mail;

use App\Models\HouseBill;
use App\Support\Pdf\ArrivalNoticePdfData;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArrivalNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HouseBill $houseBill)
    {
        $this->houseBill->loadMissing(['customer', 'masterBill']);
    }

    public function envelope(): Envelope
    {
        $companyName = config('company.name');
        $hbl = $this->houseBill->house_bill;
        $obl = $this->houseBill->masterBill?->ocean_bill_of_lading;
        $subject = "Arrival Notice - {$hbl}";
        if ($obl) {
            $subject .= " (B/L: {$obl})";
        }
        $subject .= " - {$companyName}";

        return new Envelope(
            subject: $subject,
            replyTo: filled(config('company.email')) ? [config('company.email')] : [],
        );
    }

    public function content(): Content
    {
        $this->houseBill->loadMissing([
            'customer',
            'masterBill.vessel',
            'masterBill.carrier',
            'masterBill.origin',
            'masterBill.portOfDischarge',
            'masterBill.destination',
            'containers',
        ]);

        return new Content(
            markdown: 'emails.arrival_notices.sent',
            with: [
                'houseBill' => $this->houseBill,
                'customer' => $this->houseBill->customer,
                'masterBill' => $this->houseBill->masterBill,
                'company' => config('company'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = DocumentPdf::make(
            'pdf.arrival_notices.show',
            ArrivalNoticePdfData::for($this->houseBill),
            PdfFilename::forArrivalNotice($this->houseBill),
        );

        return [
            $pdf->toMailAttachment(),
        ];
    }
}
