<?php

namespace App\Filament\Resources\HouseBills\Pages;

use App\Filament\Resources\HouseBills\HouseBillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHouseBills extends ListRecords
{
    protected static string $resource = HouseBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New House Bill')
                ->icon('heroicon-o-plus'),
        ];
    }
}
