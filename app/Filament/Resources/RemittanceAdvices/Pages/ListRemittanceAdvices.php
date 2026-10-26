<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRemittanceAdvices extends ListRecords
{
    protected static string $resource = RemittanceAdviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Remittance Advice')
                ->icon('heroicon-o-plus'),
        ];
    }
}
