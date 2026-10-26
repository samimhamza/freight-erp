<?php

namespace App\Filament\Resources\ExportAgents;

use App\Filament\Resources\ExportAgents\Pages\ListExportAgentActivities;
use App\Filament\Resources\ExportAgents\Pages\ListExportAgents;
use App\Filament\Resources\ExportAgents\Pages\ViewExportAgent;
use App\Filament\Resources\ExportAgents\Schemas\ExportAgentForm;
use App\Filament\Resources\ExportAgents\Tables\ExportAgentsTable;
use App\Models\ExportAgent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ExportAgentResource extends Resource
{
    protected static ?string $model = ExportAgent::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chevron-double-up';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return ExportAgentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExportAgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExportAgents::route('/'),
            'view' => ViewExportAgent::route('/{record}/view'),
            'activities' => ListExportAgentActivities::route('/{record}/activities'),
        ];
    }
}
