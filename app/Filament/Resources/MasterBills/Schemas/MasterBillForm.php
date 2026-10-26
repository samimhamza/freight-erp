<?php

namespace App\Filament\Resources\MasterBills\Schemas;

use App\Filament\Resources\Carriers\Schemas\CarrierForm;
use App\Filament\Resources\ExportAgents\Schemas\ExportAgentForm;
use App\Filament\Resources\HouseBills\Schemas\HouseBillForm;
use App\Filament\Resources\ImportAgents\Schemas\ImportAgentForm;
use App\Filament\Resources\Locations\Schemas\LocationForm;
use App\Filament\Resources\Vessels\Schemas\VesselForm;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Vessel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterBillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getCarrierSelect(): Select
    {
        return Select::make('carrier_id')
            ->label('Carrier')
            ->relationship('carrier', 'name')
            ->createOptionForm(CarrierForm::components())
            ->getOptionLabelFromRecordUsing(fn (Carrier $record): string => $record->code ? "{$record->code}" : $record->name)
            ->searchable()
            ->preload();
    }

    public static function getExportAgentSelect(): Select
    {
        return Select::make('export_agent_id')
            ->label('Export Agent')
            ->relationship('exportAgent', 'name')
            ->createOptionForm(ExportAgentForm::components())
            ->searchable()
            ->preload();
    }

    public static function getImportAgentSelect(): Select
    {
        return Select::make('import_agent_id')
            ->label('Import Agent')
            ->relationship('importAgent', 'name')
            ->createOptionForm(ImportAgentForm::components())
            ->searchable()
            ->preload();
    }

    public static function getConsoleDetailsSchema(): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('date')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('console')
                        ->label('CONSOLE')
                        ->required()
                        ->unique(table: 'master_bills', ignoreRecord: true)->live(onBlur: true)
                        ->maxLength(32),
                    TextInput::make('ocean_bill_of_lading')
                        ->label('OCEAN BILL OF LADING')
                        ->required()
                        ->unique(table: 'master_bills', ignoreRecord: true)->live(onBlur: true)
                        ->maxLength(32),
                    self::getCarrierSelect(),
                    self::getExportAgentSelect(),
                    self::getImportAgentSelect(),
                ]),
        ];
    }

    public static function getShipmentDetailsSchema(bool $showVessel = true): array
    {
        return [
            Section::make('Shipment Details')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('vessel_id')
                                ->label('Vessel')
                                ->relationship('vessel', 'code')
                                ->createOptionForm(VesselForm::components())
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Vessel $record): string => "{$record->name}")
                                ->visible($showVessel)
                                ->searchable()
                                ->preload(),
                            Select::make('origin_id')
                                ->label('Origin')
                                ->relationship('origin', 'code')
                                ->createOptionForm(LocationForm::components())
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Location $record): string => "{$record->name} ({$record->code})")
                                ->searchable()
                                ->preload(),
                            Select::make('destination_id')
                                ->label('Destination')
                                ->relationship('destination', 'code')
                                ->createOptionForm(LocationForm::components())
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Location $record): string => "{$record->name} ({$record->code})")
                                ->searchable()
                                ->preload(),
                            Select::make('port_of_discharge_id')
                                ->label('Port of Discharge')
                                ->relationship('portOfDischarge', 'code')
                                ->createOptionForm(LocationForm::components())
                                ->getOptionLabelFromRecordUsing(fn (Location $record): string => "{$record->name} ({$record->code})")
                                ->searchable()
                                ->preload(),

                        ]),
                    DatePicker::make('etd')
                        ->label('ETD (Estimated Time of Departure)')
                        ->required(),
                    DatePicker::make('eta')
                        ->label('ETA (Estimated Time of Arrival)')
                        ->required(),
                    DatePicker::make('ata')
                        ->label('ATA (Actual Time of Arrival)'),
                    Select::make('mode')
                        ->options([
                            'SEA',
                            'AIR',
                        ])
                        ->label('Mode'),
                    TextInput::make('customs_entry_number')
                        ->label('Customs Entry Number'),
                    TextInput::make('master_freight')
                        ->label('Master Freight'),
                ])
                ->columns(3),
        ];
    }

    public static function getHouseBillDetailsSchema(): array
    {
        return [
            Repeater::make('House Bills')
                ->columnSpanFull()
                ->relationship('houseBills')
                ->schema(HouseBillForm::components())
                ->columns(2)
                ->collapsible()
                ->reorderable()
                ->reorderableWithButtons(),
        ];
    }
}
