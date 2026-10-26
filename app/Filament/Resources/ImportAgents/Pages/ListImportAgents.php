<?php

namespace App\Filament\Resources\ImportAgents\Pages;

use App\Filament\Resources\ImportAgents\ImportAgentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportAgents extends ListRecords
{
    protected static string $resource = ImportAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
