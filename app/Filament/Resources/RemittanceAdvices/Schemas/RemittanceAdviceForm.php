<?php

namespace App\Filament\Resources\RemittanceAdvices\Schemas;

use App\Filament\Resources\Carriers\Schemas\CarrierForm;
use App\Models\Carrier;
use App\Models\MasterBill;
use App\Models\RemittanceAdviceItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RemittanceAdviceForm
{
    public static function configure(Schema $schema, ?int $masterBillId = null): Schema
    {
        return $schema->components(self::components($masterBillId));
    }

    public static function components(?int $masterBillId = null): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('advice_number')
                        ->label('Advice Number')
                        ->required()
                        ->maxLength(32),
                    Select::make('carrier_id')
                        ->label('Carrier')
                        ->relationship(
                            'carrier',
                            'name',
                            modifyQueryUsing: fn ($query, Get $get) => $query->when(
                                $masterBillId ?? $get('master_bill_id'),
                                fn ($q, $mbId) => $q->whereHas('masterBills', fn ($mb) => $mb->where('master_bills.id', $mbId))
                            )
                        )
                        ->createOptionForm(CarrierForm::components())
                        ->getOptionLabelFromRecordUsing(fn (Carrier $record): string => $record->code ? "{$record->code}" : $record->name)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->default(fn () => $masterBillId ? MasterBill::find($masterBillId)?->carrier_id : null)
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            if ($state) {
                                $currentMbId = $get('master_bill_id');
                                if ($currentMbId) {
                                    $mb = MasterBill::find($currentMbId);
                                    if ($mb && (int) $mb->carrier_id !== (int) $state) {
                                        $set('master_bill_id', null);
                                    }
                                }
                            } else {
                                $set('master_bill_id', null);
                            }
                        }),
                    Select::make('master_bill_id')
                        ->label('Master Bill')
                        ->relationship(
                            'masterBill',
                            'ocean_bill_of_lading',
                            modifyQueryUsing: fn ($query, Get $get) => $query->when(
                                $get('carrier_id'),
                                fn ($q, $carrierId) => $q->where('master_bills.carrier_id', $carrierId)
                            )
                        )
                        ->getOptionLabelFromRecordUsing(fn (MasterBill $record) => "{$record->ocean_bill_of_lading} (Console: {$record->console}".($record->carrier ? ", Carrier: {$record->carrier->name}" : '').')')
                        ->searchable(['ocean_bill_of_lading', 'console'])
                        ->preload()
                        ->required()
                        ->visible(fn (): bool => ! $masterBillId)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $masterBill = MasterBill::find($state);
                                if ($masterBill?->carrier_id) {
                                    $set('carrier_id', $masterBill->carrier_id);
                                }
                            }
                        })
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $carrierId = $get('carrier_id');
                                if ($value && $carrierId) {
                                    $masterBill = MasterBill::find($value);
                                    if ($masterBill && (int) $masterBill->carrier_id !== (int) $carrierId) {
                                        $fail('The selected master bill does not belong to the selected carrier.');
                                    }
                                }
                            },
                        ]),
                    DatePicker::make('advice_date')
                        ->label('Advice Date')
                        ->required()
                        ->live(),
                    DatePicker::make('due_date')
                        ->required()
                        ->live()
                        ->minDate(fn (Get $get): ?string => $get('advice_date') ?? $get('invoice_date'))
                        ->afterOrEqual('advice_date'),
                ]),
        ];
    }

    public static function getAdviceItemsSchema(): array
    {
        return [
            Repeater::make('Remittance Items')
                ->columnSpanFull()
                ->relationship('items')
                ->schema(RemittanceAdviceItem::form())
                ->defaultItems(1)
                ->columns(2)
                ->collapsible()
                ->reorderable()
                ->reorderableWithButtons(),
        ];
    }

    public static function getInvoiceItemsSchema(): array
    {
        return self::getAdviceItemsSchema();
    }
}
