<?php

namespace App\Mail;

use App\Models\RemittanceAdvice;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\PdfFilename;
use App\Support\Pdf\RemittanceAdvicePdfData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RemittanceAdviceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RemittanceAdvice $remittanceAdvice)
    {
        $remittanceAdvice->loadMissing(['carrier', 'masterBill.carrier']);
    }

    public function envelope(): Envelope
    {
        $companyName = config('company.name');
        $carrierName = $this->remittanceAdvice->carrier?->name ?? $this->remittanceAdvice->masterBill?->carrier?->name;
        $carrier = $carrierName ? " ({$carrierName})" : '';

        return new Envelope(
            subject: "Remittance Advice {$this->remittanceAdvice->advice_number}{$carrier} - {$companyName}",
            replyTo: filled(config('company.email')) ? [config('company.email')] : [],
        );
    }

    public function content(): Content
    {
        $this->remittanceAdvice->loadMissing(['carrier', 'masterBill.carrier']);

        return new Content(
            markdown: 'emails.remittance_advices.sent',
            with: [
                'remittanceAdvice' => $this->remittanceAdvice,
                'remittanceInvoice' => $this->remittanceAdvice,
                'payableInvoice' => $this->remittanceAdvice,
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
            'pdf.remittance_advices.show',
            RemittanceAdvicePdfData::for($this->remittanceAdvice),
            PdfFilename::forRemittanceAdvice($this->remittanceAdvice),
        );

        return [
            Attachment::fromData(fn () => $pdf->inline(), PdfFilename::forRemittanceAdvice($this->remittanceAdvice).'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

