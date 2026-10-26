<?php

namespace App\Filament\Resources\Consignors\Pages;

use App\Filament\Resources\Consignors\ConsignorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsignor extends EditRecord
{
    protected static string $resource = ConsignorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
