<?php

namespace App\Filament\Resources\MasterBills\RelationManagers;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Filament\Resources\RemittanceAdvices\Tables\RemittanceAdvicesTable;
use Filament\Actions\CreateAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RemittanceAdvicesRelationManager extends RelationManager
{
    protected static string $relationship = 'remittanceAdvices';

    protected static ?string $title = 'Remittance Advices';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn ($record) => RemittanceAdviceResource::getUrl('view', ['record' => $record])
            )
            ->recordTitleAttribute('id')
            ->columns(RemittanceAdvicesTable::columns())
            ->filters(RemittanceAdvicesTable::filters())
            ->headerActions([
                CreateAction::make()
                    ->label('New Remittance Advice')
                    ->icon('heroicon-o-plus')
                    ->steps(RemittanceAdviceResource::getSteps(masterBillId: $this->getOwnerRecord()->id)),
            ])
            ->recordActions([
                ViewAction::make()->label('')
                    ->url(fn ($record) => RemittanceAdviceResource::getUrl('view', ['record' => $record])),
                ...RemittanceAdviceResource::actions(true),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
