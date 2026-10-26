<?php

namespace App\Filament\Resources\RemittanceAdvices\RelationManagers;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
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
            ->components(RemittanceAdviceItem::form());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(RemittanceAdviceItem::columns())
            ->filters([
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->can('ForceDelete:RemittanceAdvice') || auth()->user()->can('ForceDelete:RemittanceInvoice') || auth()->user()->can('ForceDelete:PayableInvoice') || auth()->user()->can('ForceDelete:Invoice')),
                ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    })
                    ->visible(RemittanceAdvice::showItemActions($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->visible(fn (RemittanceAdviceItem $record): bool => RemittanceAdvice::showItemActions($this->getOwnerRecord()) && ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                DeleteAction::make()
                    ->label('')
                    ->visible(fn (RemittanceAdviceItem $record): bool => RemittanceAdvice::showItemActions($this->getOwnerRecord()) && ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnDeleteChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                RestoreAction::make()->label('')
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                ForceDeleteAction::make()->label('')
                    ->before(function ($record) {
                        RemittanceAdvice::updateStatusOnDeleteChild($record);
                    })
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshViewPage');
                    }),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (RemittanceAdviceItem $record): string => RemittanceAdviceResource::getUrl('item-activities', ['record' => $record]))
                    ->visible(fn (RemittanceAdviceItem $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
