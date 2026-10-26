<?php

use App\Jobs\CheckOverdueInvoices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckOverdueInvoices)->daily();
Schedule::command('invoices:send-due-reminders')->daily();
Schedule::command('arrival-notices:send')->daily();
Schedule::command('activitylog:clean')->daily();
