<?php

namespace App\Filament\Resources\HouseBills;

use App\Filament\Resources\HouseBills\Pages\CreateHouseBill;
use App\Filament\Resources\HouseBills\Pages\EditHouseBill;
use App\Filament\Resources\HouseBills\Pages\ListHouseBillActivities;
use App\Filament\Resources\HouseBills\Pages\ListHouseBills;
use App\Filament\Resources\HouseBills\Pages\ViewHouseBill;
use App\Filament\Resources\HouseBills\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\HouseBills\Schemas\HouseBillForm;
use App\Filament\Resources\HouseBills\Tables\HouseBillsTable;
use App\Models\HouseBill;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HouseBillResource extends Resource
{
    protected static ?string $model = HouseBill::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return HouseBillForm::configure($schema, showMasterBill: true);
    }

    public static function table(Table $table): Table
    {
        return HouseBillsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHouseBills::route('/'),
            'create' => CreateHouseBill::route('/create'),
            'view' => ViewHouseBill::route('/{record}/view'),
            'edit' => EditHouseBill::route('/{record}/edit'),
            'activities' => ListHouseBillActivities::route('/{record}/activities'),
        ];
    }
}
