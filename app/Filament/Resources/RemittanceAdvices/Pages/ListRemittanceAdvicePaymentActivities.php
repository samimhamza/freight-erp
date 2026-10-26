<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Models\RemittancePayment;
use Filament\Forms\Components\Select;

class ListRemittanceAdvicePaymentActivities extends ListRemittanceAdviceChildActivities
{
    protected static function childModelClass(): string
    {
        return RemittancePayment::class;
    }

    protected function formSchemaComponents(): array
    {
        return [
            Select::make('remittance_advice_id')
                ->relationship('remittanceAdvice', 'advice_number')
                ->searchable()
                ->preload()
                ->required(),
            ...RemittancePayment::form(PHP_FLOAT_MAX),
        ];
    }

    public function getRecordTitle(): string
    {
        $record = $this->getRecord();

        return 'Payment #'.$record->payment_number;
    }
}
