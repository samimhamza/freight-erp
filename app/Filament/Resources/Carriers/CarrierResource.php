<?php

namespace App\Filament\Resources\Carriers;

use App\Filament\Resources\Carriers\Pages\ListCarrierActivities;
use App\Filament\Resources\Carriers\Pages\ListCarriers;
use App\Filament\Resources\Carriers\Pages\ViewCarrier;
use App\Filament\Resources\Carriers\Schemas\CarrierForm;
use App\Filament\Resources\Carriers\Tables\CarriersTable;
use App\Models\Carrier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CarrierResource extends Resource
{
    protected static ?string $model = Carrier::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return CarrierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CarriersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarriers::route('/'),
            'view' => ViewCarrier::route('/{record}/view'),
            'activities' => ListCarrierActivities::route('/{record}/activities'),
        ];
    }
}
