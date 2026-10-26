<?php

namespace App\Filament\Resources\Charges;

use App\Filament\Resources\Charges\Pages\ListChargeActivities;
use App\Filament\Resources\Charges\Pages\ListCharges;
use App\Filament\Resources\Charges\Schemas\ChargeForm;
use App\Filament\Resources\Charges\Tables\ChargesTable;
use App\Models\Charge;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Invoices';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ChargeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChargesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharges::route('/'),
            'activities' => ListChargeActivities::route('/{record}/activities'),
        ];
    }
}
