<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected $listeners = ['refreshChildRelations' => '$refresh'];

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(InvoiceItem::form());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(InvoiceItem::columns())
            ->filters([
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->can('ForceDelete:Invoice')),
                ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    })
                    ->visible(Invoice::showItemActions($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->visible(fn (InvoiceItem $record): bool => Invoice::showItemActions($this->getOwnerRecord()) && ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                DeleteAction::make()
                    ->label('')
                    ->visible(fn (InvoiceItem $record): bool => Invoice::showItemActions($this->getOwnerRecord()) && ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnDeleteChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                RestoreAction::make()->label('')
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                ForceDeleteAction::make()->label('')
                    ->before(function ($record) {
                        Invoice::updateStatusOnDeleteChild($record);
                    })
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshViewPage');
                    }),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (InvoiceItem $record): string => InvoiceResource::getUrl('item-activities', ['record' => $record]))
                    ->visible(fn (InvoiceItem $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
