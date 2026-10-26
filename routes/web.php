<?php

use App\Http\Controllers\ArrivalNoticePrintController;
use App\Http\Controllers\HouseBillPrintController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\MasterBillPrintController;
use App\Http\Controllers\RemittanceAdvicePrintController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('house-bills/{houseBill}/print', HouseBillPrintController::class)
        ->name('house-bills.print');

    Route::get('house-bills/{houseBill}/arrival-notice', ArrivalNoticePrintController::class)
        ->name('house-bills.arrival-notice');

    Route::get('invoices/{invoice}/print', InvoicePrintController::class)
        ->name('invoices.print');

    Route::get('remittance-advices/{remittanceAdvice}/print', RemittanceAdvicePrintController::class)
        ->name('remittance-advices.print');

    Route::get('remittance-invoices/{remittanceAdvice}/print', RemittanceAdvicePrintController::class)
        ->name('remittance-invoices.print');

    Route::get('payable-invoices/{remittanceAdvice}/print', RemittanceAdvicePrintController::class)
        ->name('payable-invoices.print');

    Route::get('master-bills/{masterBill}/print', MasterBillPrintController::class)
        ->name('master-bills.print');
});
