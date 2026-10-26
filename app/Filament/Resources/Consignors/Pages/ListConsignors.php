<?php

namespace App\Filament\Resources\Consignors\Pages;

use App\Filament\Resources\Consignors\ConsignorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsignors extends ListRecords
{
    protected static string $resource = ConsignorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Consignor')
                ->icon('heroicon-o-plus'),
        ];
    }
}
