<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;

class CreateRemittanceAdvice extends CreateRecord
{
    use HasWizard;

    protected static string $resource = RemittanceAdviceResource::class;

    protected function getSteps(): array
    {
        return RemittanceAdviceResource::getSteps();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = InvoiceStatus::DRAFT;

        return $data;
    }
}
