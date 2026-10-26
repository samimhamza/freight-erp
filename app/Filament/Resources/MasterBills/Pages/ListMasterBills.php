<?php

namespace App\Filament\Resources\MasterBills\Pages;

use App\Filament\Resources\MasterBills\MasterBillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterBills extends ListRecords
{
    protected static string $resource = MasterBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Master Bill')
                ->icon('heroicon-o-plus'),
        ];
    }
}
