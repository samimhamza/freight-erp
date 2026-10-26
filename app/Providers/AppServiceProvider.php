<?php

namespace App\Providers;

use App\Support\Pdf\DomPdfDriver;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('laravel-pdf.driver.dompdf', function () {
            return new DomPdfDriver(config('laravel-pdf.dompdf', []));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => 'App\Models\User',
            'consignor' => 'App\Models\Consignor',
            'customer' => 'App\Models\Customer',
            'house_bill' => 'App\Models\HouseBill',
            'master_bill' => 'App\Models\MasterBill',
            'location' => 'App\Models\Location',
            'vehicle' => 'App\Models\Vehicle',
            'charge' => 'App\Models\Charge',
            'invoice' => 'App\Models\Invoice',
            'payment' => 'App\Models\Payment',
            'invoice_item' => 'App\Models\InvoiceItem',
            'modal_print' => 'App\Models\ModalPrint',
            'container' => 'App\Models\Container',
            'house_bill_container' => 'App\Models\HouseBillContainer',
            'country' => 'App\Models\Country',
            'vessel' => 'App\Models\Vessel',
            'remittance_advice' => 'App\Models\RemittanceAdvice',
            'remittance_advice_item' => 'App\Models\RemittanceAdviceItem',
            'remittance_payment' => 'App\Models\RemittancePayment',
            'remittance_invoice' => 'App\Models\RemittanceAdvice',
            'remittance_invoice_item' => 'App\Models\RemittanceAdviceItem',
            'payable_invoice' => 'App\Models\RemittanceAdvice',
            'payable_invoice_item' => 'App\Models\RemittanceAdviceItem',
            'payable_payment' => 'App\Models\RemittancePayment',
            'export_agent' => 'App\Models\ExportAgent',
            'import_agent' => 'App\Models\ImportAgent',
            'carrier' => 'App\Models\Carrier',
        ]);

        DateTimePicker::configureUsing(function (DateTimePicker $component): void {
            $component->timezone('Asia/Dubai');
        });

        TextColumn::configureUsing(function (TextColumn $column): void {
            $column->timezone('Asia/Dubai');
        });

        TextEntry::configureUsing(function (TextEntry $entry): void {
            $entry->timezone('Asia/Dubai');
        });

        DatePicker::configureUsing(function ($action) {
            return $action->native(false);
        });

        DateTimePicker::configureUsing(function ($action) {
            return $action->native(false);
        });

        Select::configureUsing(function ($action) {
            return $action->native(false);
        });

        DeleteAction::configureUsing(function ($action) {
            return $action->icon('heroicon-o-trash');
        });

        EditAction::configureUsing(function ($action) {
            return $action->icon('heroicon-o-pencil');
        });

        ViewAction::configureUsing(function ($action) {
            return $action->icon('heroicon-o-eye');
        });
    }
}
