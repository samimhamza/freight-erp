<?php

namespace App\Filament\Resources\ImportAgents;

use App\Filament\Resources\ImportAgents\Pages\ListImportAgentActivities;
use App\Filament\Resources\ImportAgents\Pages\ListImportAgents;
use App\Filament\Resources\ImportAgents\Pages\ViewImportAgent;
use App\Filament\Resources\ImportAgents\Schemas\ImportAgentForm;
use App\Filament\Resources\ImportAgents\Tables\ImportAgentsTable;
use App\Models\ImportAgent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ImportAgentResource extends Resource
{
    protected static ?string $model = ImportAgent::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chevron-double-down';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ImportAgentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportAgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportAgents::route('/'),
            'view' => ViewImportAgent::route('/{record}/view'),
            'activities' => ListImportAgentActivities::route('/{record}/activities'),
        ];
    }
}
