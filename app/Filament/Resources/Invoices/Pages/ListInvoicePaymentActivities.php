<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Models\Payment;
use Filament\Forms\Components\Select;

class ListInvoicePaymentActivities extends ListInvoiceChildActivities
{
    protected static function childModelClass(): string
    {
        return Payment::class;
    }

    protected function formSchemaComponents(): array
    {
        return [
            Select::make('invoice_id')
                ->relationship('invoice', 'invoice_number')
                ->searchable()
                ->preload()
                ->required(),
            ...Payment::form(PHP_FLOAT_MAX),
        ];
    }

    public function getRecordTitle(): string
    {
        $record = $this->getRecord();

        return 'Payment #'.$record->payment_number;
    }
}
