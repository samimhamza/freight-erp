<?php

namespace App\Console\Commands;

use App\Models\MasterBill;
use App\Services\SendArrivalNotice;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('arrival-notices:send {--days=2 : Number of days before ATA to send notice} {--force : Send notice even if already sent}')]
#[Description('Send automatic arrival notice emails to customers before master bill ATA')]
class SendArrivalNoticesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendArrivalNotice $sendArrivalNotice): int
    {
        $days = (int) $this->option('days');
        $force = (bool) $this->option('force');
        $targetDate = Carbon::today()->addDays($days);

        $this->info("Checking for master bills with ATA on or before {$targetDate->toDateString()} ({$days} day(s) advance notice)...");

        $masterBillsQuery = MasterBill::query()
            ->whereNotNull('ata')
            ->whereDate('ata', '<=', $targetDate)
            ->whereDate('ata', '>=', Carbon::today())
            ->whereHas('houseBills', function ($query) use ($force) {
                if (! $force) {
                    $query->whereNull('arrival_notice_sent_at');
                }
                $query->whereHas('customer', function ($q) {
                    $q->whereNotNull('email')->where('email', '!=', '');
                });
            })
            ->with(['houseBills' => function ($query) use ($force) {
                if (! $force) {
                    $query->whereNull('arrival_notice_sent_at');
                }
                $query->whereHas('customer', function ($q) {
                    $q->whereNotNull('email')->where('email', '!=', '');
                })->with(['customer', 'masterBill']);
            }]);

        $masterBills = $masterBillsQuery->get();

        if ($masterBills->isEmpty()) {
            $this->info('No eligible shipments found for arrival notices.');

            return self::SUCCESS;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($masterBills as $masterBill) {
            $this->line("Processing Master B/L {$masterBill->ocean_bill_of_lading} (ATA: {$masterBill->ata->format('Y-m-d')})...");

            foreach ($masterBill->houseBills as $houseBill) {
                try {
                    $sendArrivalNotice->send($houseBill);
                    $this->line("  ✓ Sent arrival notice for House B/L {$houseBill->house_bill} to {$houseBill->customer->email} ({$houseBill->customer->name})");
                    $sentCount++;
                } catch (Throwable $e) {
                    $this->error("  ✗ Failed sending for House B/L {$houseBill->house_bill}: {$e->getMessage()}");
                    $failedCount++;
                }
            }
        }

        $this->info("Completed. Successfully sent: {$sentCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
