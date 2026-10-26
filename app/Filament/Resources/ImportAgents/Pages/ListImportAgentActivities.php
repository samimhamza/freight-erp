<?php

namespace App\Filament\Resources\ImportAgents\Pages;

use App\Filament\Resources\ImportAgents\ImportAgentResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListImportAgentActivities extends ListActivities
{
    protected static string $resource = ImportAgentResource::class;
}
