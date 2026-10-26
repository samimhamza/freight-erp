<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;

class EditRemittanceAdvice extends EditRecord
{
    use HasWizard;

    protected static string $resource = RemittanceAdviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSteps(): array
    {
        return RemittanceAdviceResource::getSteps();
    }
}
