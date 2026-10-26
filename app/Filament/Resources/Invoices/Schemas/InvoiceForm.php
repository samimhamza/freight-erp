<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceType;
use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema, ?int $houseBillId = null, ?int $customerId = null): Schema
    {
        return $schema->components(self::components($houseBillId, $customerId));
    }

    public static function isClearance(Get $get): bool
    {
        $type = $get('type');

        if ($type instanceof InvoiceType) {
            return $type === InvoiceType::CLEARANCE;
        }

        return $type === InvoiceType::CLEARANCE->value;
    }

    public static function isShipping(Get $get): bool
    {
        $type = $get('type');

        if ($type instanceof InvoiceType) {
            return $type === InvoiceType::SHIPPING;
        }

        return $type === InvoiceType::SHIPPING->value;
    }

    public static function components(?int $houseBillId = null, ?int $customerId = null): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('invoice_number')
                        ->required()
                        ->maxLength(32),

                    Select::make('type')
                        ->label('Type')
                        ->options(InvoiceType::class)
                        ->default(InvoiceType::SHIPPING->value)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $isShipping = ($state instanceof InvoiceType)
                                ? $state === InvoiceType::SHIPPING
                                : $state === InvoiceType::SHIPPING->value;

                            if ($isShipping) {
                                $set('house_bill_source', 'existing');
                                $set('house_bill', null);
                                $set('container_numbers', []);
                            }
                        }),

                    Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->visible(fn (): bool => ! $customerId)
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            $currentHbId = $get('house_bill_id');
                            if ($currentHbId) {
                                $hb = HouseBill::find($currentHbId);
                                if ($hb && (int) $hb->customer_id !== (int) $state) {
                                    $set('house_bill_id', null);
                                }
                            }
                        }),

                    DatePicker::make('invoice_date')
                        ->required()
                        ->live(),

                    DatePicker::make('due_date')
                        ->required()
                        ->live()
                        ->minDate(fn (Get $get): ?string => $get('invoice_date'))
                        ->afterOrEqual('invoice_date'),

                    ToggleButtons::make('house_bill_source')
                        ->label('House B/L Source')
                        ->options([
                            'existing' => 'Select Existing House B/L',
                            'manual' => 'Enter Manual House B/L & Containers',
                        ])
                        ->icons([
                            'existing' => 'heroicon-o-circle-stack',
                            'manual' => 'heroicon-o-pencil-square',
                        ])
                        ->colors([
                            'existing' => 'info',
                            'manual' => 'warning',
                        ])
                        ->default('existing')
                        ->inline()
                        ->live()
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => self::isClearance($get) && ! $houseBillId)
                        ->afterStateHydrated(function (ToggleButtons $component, ?Invoice $record) {
                            if ($record && filled($record->house_bill)) {
                                $component->state('manual');
                            } else {
                                $component->state('existing');
                            }
                        })
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state === 'manual') {
                                $set('house_bill_id', null);
                            } else {
                                $set('house_bill', null);
                                $set('container_numbers', []);
                            }
                        }),

                    Select::make('house_bill_id')
                        ->label('House B/L')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpan(fn (Get $get): int => self::isClearance($get) ? 2 : 1)
                        ->visible(function (Get $get) use ($houseBillId): bool {
                            if ($houseBillId) {
                                return false;
                            }

                            if (self::isShipping($get)) {
                                return true;
                            }

                            return ($get('house_bill_source') ?? 'existing') === 'existing';
                        })
                        ->options(function (Get $get) use ($customerId): array {
                            $selectedCustomer = $customerId ?? $get('customer_id');

                            return HouseBill::query()
                                ->when($selectedCustomer, fn ($q) => $q->where('customer_id', $selectedCustomer))
                                ->pluck('house_bill', 'id')
                                ->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search, Get $get) use ($customerId): array {
                            $selectedCustomer = $customerId ?? $get('customer_id');

                            return HouseBill::query()
                                ->when($selectedCustomer, fn ($q) => $q->where('customer_id', $selectedCustomer))
                                ->where('house_bill', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('house_bill', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => HouseBill::find($value)?->house_bill)
                        ->required(fn (Get $get): bool => self::isShipping($get) || ($get('house_bill_source') ?? 'existing') === 'existing')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (filled($state) && is_numeric($state)) {
                                $set('house_bill', null);
                                $set('container_numbers', []);
                                $hb = HouseBill::find($state);
                                if ($hb) {
                                    $set('customer_id', $hb->customer_id);
                                }
                            }
                        }),

                    TextInput::make('house_bill')
                        ->label('Manual House B/L Number')
                        ->placeholder('e.g. HBL-987654')
                        ->maxLength(64)
                        ->columnSpan(1)
                        ->visible(fn (Get $get): bool => self::isClearance($get) && ($get('house_bill_source') ?? 'existing') === 'manual')
                        ->required(fn (Get $get): bool => self::isClearance($get) && ($get('house_bill_source') ?? 'existing') === 'manual')
                        ->live(onBlur: true),

                    TagsInput::make('container_numbers')
                        ->label('Manual Container Numbers')
                        ->placeholder('Type container number and hit Enter')
                        ->helperText('Add one or more container numbers for this shipment. Hit Enter after each number.')
                        ->columnSpan(2)
                        ->visible(fn (Get $get): bool => self::isClearance($get) && ($get('house_bill_source') ?? 'existing') === 'manual')
                        ->required(fn (Get $get): bool => self::isClearance($get) && ($get('house_bill_source') ?? 'existing') === 'manual')
                        ->nestedRecursiveRules([
                            'string',
                            'max:64',
                        ]),
                ]),
        ];
    }

    public static function getInvoiceItemsSchema(): array
    {
        return [
            Repeater::make('Invoice Items')
                ->columnSpanFull()
                ->relationship('items')
                ->schema(InvoiceItem::form())
                ->defaultItems(1)
                ->columns(2)
                ->collapsible()
                ->reorderable()
                ->reorderableWithButtons(),
        ];
    }
}
