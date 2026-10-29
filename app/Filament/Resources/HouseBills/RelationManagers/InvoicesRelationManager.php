<?php

namespace App\Filament\Resources\HouseBills\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use Filament\Actions\CreateAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'customer',
                'houseBill.containers.container',
                'items',
            ]))
            ->recordUrl(
                fn ($record) => InvoiceResource::getUrl('view', ['record' => $record])
            )
            ->recordTitleAttribute('id')
            ->columns(InvoicesTable::columns())
            ->filters(InvoicesTable::filters())
            ->headerActions([
                CreateAction::make()
                    ->label('New Invoice')
                    ->icon('heroicon-o-plus')
                    ->steps(InvoiceResource::getSteps(houseBillId: $this->getOwnerRecord()->id)),
            ])
            ->recordActions([
                ViewAction::make()->label('')
                    ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record])),
                ...InvoiceResource::actions(true),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
