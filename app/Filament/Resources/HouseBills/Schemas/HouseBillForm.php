<?php

namespace App\Filament\Resources\HouseBills\Schemas;

use App\Filament\Resources\Consignors\Schemas\ConsignorForm;
use App\Filament\Resources\Containers\Schemas\ContainerForm;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Vehicles\Schemas\VehicleForm;
use App\Models\Vehicle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HouseBillForm
{
    public static function configure(Schema $schema, bool $showMasterBill = true): Schema
    {
        return $schema->components(self::components($showMasterBill));
    }

    public static function components(bool $showMasterBill = false): array
    {
        return [
            Section::make('House Bill Details')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('house_bill')
                        ->label('House Bill')
                        ->maxLength(32)
                        ->required()
                        ->unique(table: 'house_bills', ignoreRecord: true)->live(onBlur: true),
                    Select::make('master_bill_id')
                        ->relationship('masterBill', 'ocean_bill_of_lading')
                        ->preload()
                        ->searchable()
                        ->required()
                        ->visible($showMasterBill),
                    Select::make('consignor_id')
                        ->relationship(name: 'consignor', titleAttribute: 'name')
                        ->createOptionForm(ConsignorForm::components())
                        ->preload()
                        ->searchable(),
                    Select::make('customer_id')
                        ->label('Customer (Consignee)')
                        ->relationship(name: 'customer', titleAttribute: 'name')
                        ->createOptionForm(CustomerForm::components())
                        ->preload()
                        ->searchable()
                        ->required(),
                    Select::make('notify_party_id')
                        ->label('Notify Party')
                        ->relationship(name: 'notifyParty', titleAttribute: 'name')
                        ->createOptionForm(CustomerForm::components())
                        ->preload()
                        ->searchable(),
                ])
                ->columns(2),
            Grid::make(1)
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Container Details')
                        ->columnSpanFull()
                        ->relationship('containers')
                        ->schema(
                            [
                                Select::make('container_id')
                                    ->relationship(name: 'container', titleAttribute: 'container_number')
                                    ->createOptionForm(ContainerForm::components())
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                TextInput::make('seal')
                                    ->maxLength(32)
                                    ->required()
                                    ->unique(table: 'house_bill_containers', ignoreRecord: true)->live(onBlur: true),
                                TextInput::make('net_weight')
                                    ->label('Net Weight')
                                    ->suffix('KG')
                                    ->numeric()
                                    ->inputMode('decimal'),
                                TextInput::make('gross_weight')
                                    ->label('Gross Weight')
                                    ->required()
                                    ->suffix('KG')
                                    ->numeric()
                                    ->inputMode('decimal'),
                                TextInput::make('packages')
                                    ->label('Total Packages')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                Select::make('vehicles')
                                    ->relationship(
                                        name: 'vehicles',
                                        titleAttribute: 'vin',
                                        modifyQueryUsing: fn ($record, Builder $query) => $query->whereNull('house_bill_container_id')
                                            ->when($record, fn ($query) => $query->orWhere('house_bill_container_id', $record->id)),
                                    )
                                    ->createOptionForm(VehicleForm::components())
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->saveRelationshipsUsing(function ($record, $state) {
                                        $selectedIds = collect($state ?? [])->filter()->map(fn ($id) => (int) $id);
                                        $currentIds = $record->vehicles()->pluck('id');

                                        $toAttach = $selectedIds->diff($currentIds);
                                        $toDetach = $currentIds->diff($selectedIds);

                                        DB::transaction(function () use ($record, $toAttach, $toDetach) {
                                            if ($toAttach->isNotEmpty()) {
                                                Vehicle::whereIn('id', $toAttach)->update([
                                                    'house_bill_container_id' => $record->id,
                                                ]);
                                            }

                                            if ($toDetach->isNotEmpty()) {
                                                Vehicle::where('house_bill_container_id', $record->id)
                                                    ->whereIn('id', $toDetach)
                                                    ->update(['house_bill_container_id' => null]);
                                            }
                                        });
                                    }),
                                Textarea::make('descriptions_of_goods')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]
                        )
                        ->columns(3)
                        ->collapsible()
                        ->reorderable()
                        ->reorderableWithButtons(),
                ]),
        ];
    }
}
