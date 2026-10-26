<?php

namespace App\Filament\Resources\ExportAgents\Pages;

use App\Filament\Resources\ExportAgents\ExportAgentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExportAgents extends ListRecords
{
    protected static string $resource = ExportAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
