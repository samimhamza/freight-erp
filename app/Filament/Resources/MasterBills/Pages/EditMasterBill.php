<?php

namespace App\Filament\Resources\MasterBills\Pages;

use App\Filament\Resources\MasterBills\MasterBillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;

class EditMasterBill extends EditRecord
{
    use HasWizard;

    protected static string $resource = MasterBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Master Bill ('.$record->ocean_bill_of_lading.')')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSteps(): array
    {
        return MasterBillResource::getSteps();
    }
}
