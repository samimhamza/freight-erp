<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\RemittanceAdvice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckOverdueInvoices implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Invoice::where('status', '!=', InvoiceStatus::PAID)
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => InvoiceStatus::OVERDUE]);

        RemittanceAdvice::where('status', '!=', InvoiceStatus::PAID)
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => InvoiceStatus::OVERDUE]);
    }
}
