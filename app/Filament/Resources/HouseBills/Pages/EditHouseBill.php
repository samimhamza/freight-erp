<?php

namespace App\Filament\Resources\HouseBills\Pages;

use App\Filament\Resources\HouseBills\HouseBillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHouseBill extends EditRecord
{
    protected static string $resource = HouseBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')'),
        ];
    }
}
