<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\SendInvoiceToCustomer;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('invoices:send-due-reminders {--days=1 : Number of days before due date}')]
#[Description('Send automatic reminder emails for invoices approaching due date')]
class SendDueInvoiceReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendInvoiceToCustomer $sendInvoiceToCustomer): int
    {
        $days = (int) $this->option('days');
        $targetDate = Carbon::today()->addDays($days);

        $this->info("Checking for invoices due on {$targetDate->toDateString()} ({$days} day(s) remaining)...");

        $invoices = Invoice::query()
            ->with(['customer', 'houseBill', 'items', 'payments'])
            ->whereDate('due_date', $targetDate)
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED])
            ->whereHas('customer', function ($query) {
                $query->whereNotNull('email')->where('email', '!=', '');
            })
            ->where(function ($query) {
                $query->whereNull('sent_at')
                    ->orWhereDate('sent_at', '<', Carbon::today());
            })
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found matching criteria.');

            return self::SUCCESS;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($invoices as $invoice) {
            try {
                $sendInvoiceToCustomer->send($invoice);
                $this->line("Sent reminder for invoice #{$invoice->invoice_number} to {$invoice->customer->email}");
                $sentCount++;
            } catch (Throwable $e) {
                $this->error("Failed sending invoice #{$invoice->invoice_number}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->info("Completed. Successfully sent: {$sentCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
