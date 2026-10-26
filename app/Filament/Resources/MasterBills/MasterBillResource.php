<?php

namespace App\Filament\Resources\MasterBills;

use App\Filament\Resources\MasterBills\Pages\CreateMasterBill;
use App\Filament\Resources\MasterBills\Pages\EditMasterBill;
use App\Filament\Resources\MasterBills\Pages\ListMasterBillActivities;
use App\Filament\Resources\MasterBills\Pages\ListMasterBills;
use App\Filament\Resources\MasterBills\Pages\ViewMasterBill;
use App\Filament\Resources\MasterBills\RelationManagers\HouseBillsRelationManager;
use App\Filament\Resources\MasterBills\RelationManagers\RemittanceAdvicesRelationManager;
use App\Filament\Resources\MasterBills\Schemas\MasterBillForm;
use App\Filament\Resources\MasterBills\Tables\MasterBillsTable;
use App\Models\MasterBill;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterBillResource extends Resource
{
    protected static ?string $model = MasterBill::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MasterBillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterBillsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HouseBillsRelationManager::class,
            RemittanceAdvicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasterBills::route('/'),
            'view' => ViewMasterBill::route('/{record}/view'),
            'create' => CreateMasterBill::route('/create'),
            'edit' => EditMasterBill::route('/{record}/edit'),
            'activities' => ListMasterBillActivities::route('/{record}/activities'),
        ];
    }

    public static function getSteps(bool $showVessel = true): array
    {
        return [
            Step::make('Console Details')
                ->schema(MasterBillForm::getConsoleDetailsSchema())
                ->columns(3),
            Step::make('Shipment Details')
                ->schema(MasterBillForm::getShipmentDetailsSchema(showVessel: $showVessel))
                ->columns(3),
            Step::make('House Bill Details')
                ->schema(MasterBillForm::getHouseBillDetailsSchema()),
        ];
    }
}
