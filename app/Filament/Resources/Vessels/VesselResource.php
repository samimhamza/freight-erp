<?php

namespace App\Filament\Resources\Vessels;

use App\Filament\Resources\Vessels\Pages\ListVesselActivities;
use App\Filament\Resources\Vessels\Pages\ListVessels;
use App\Filament\Resources\Vessels\Pages\ViewVessel;
use App\Filament\Resources\Vessels\RelationManagers\MasterBillsRelationManager;
use App\Filament\Resources\Vessels\Schemas\VesselForm;
use App\Filament\Resources\Vessels\Tables\VesselsTable;
use App\Models\Vessel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VesselResource extends Resource
{
    protected static ?string $model = Vessel::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return VesselForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VesselsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MasterBillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVessels::route('/'),
            'view' => ViewVessel::route('/{record}/view'),
            'activities' => ListVesselActivities::route('/{record}/activities'),
        ];
    }
}
