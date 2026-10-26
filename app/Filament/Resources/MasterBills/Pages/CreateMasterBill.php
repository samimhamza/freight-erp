<?php

namespace App\Filament\Resources\MasterBills\Pages;

use App\Filament\Resources\MasterBills\MasterBillResource;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;

class CreateMasterBill extends CreateRecord
{
    use HasWizard;

    protected static string $resource = MasterBillResource::class;

    protected function getSteps(): array
    {
        return MasterBillResource::getSteps();
    }
}
