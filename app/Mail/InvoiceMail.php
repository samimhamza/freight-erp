<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\InvoicePdfData;
use App\Support\Pdf\PdfFilename;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
        $invoice->loadMissing('customer');
    }

    public function envelope(): Envelope
    {
        $companyName = config('company.name');

        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from {$companyName}",
            replyTo: filled(config('company.email')) ? [config('company.email')] : [],
        );
    }

    public function content(): Content
    {
        $this->invoice->loadMissing('customer');

        return new Content(
            markdown: 'emails.invoices.sent',
            with: [
                'invoice' => $this->invoice,
                'customer' => $this->invoice->customer,
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
            'pdf.invoices.show',
            InvoicePdfData::for($this->invoice),
            PdfFilename::forInvoice($this->invoice),
        );

        return [
            $pdf->toMailAttachment(),
        ];
    }
}
