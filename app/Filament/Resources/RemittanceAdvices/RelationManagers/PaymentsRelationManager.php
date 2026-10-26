<?php

namespace App\Filament\Resources\RemittanceAdvices\RelationManagers;

use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Models\RemittanceAdvice;
use App\Models\RemittancePayment;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected $listeners = ['refreshChildRelations' => '$refresh'];

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns(RemittancePayment::columns())
            ->filters([
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->can('ForceDelete:RemittanceAdvice') || auth()->user()->can('ForceDelete:RemittanceInvoice') || auth()->user()->can('ForceDelete:PayableInvoice') || auth()->user()->can('ForceDelete:Invoice')),
                ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Payment')
                    ->icon('heroicon-o-plus')
                    ->schema(RemittancePayment::form(
                        $this->getOwnerRecord()->total_remaining,
                        $this->getOwnerRecord()->advice_date ?? $this->getOwnerRecord()->invoice_date
                    ))
                    ->mutateDataUsing(function (array $data): array {
                        $data['payment_number'] = $this->getOwnerRecord()->payments()->count() + 1;

                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    })
                    ->visible(RemittanceAdvice::showCreatePayment($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()->label('')
                    ->schema(
                        RemittancePayment::form(
                            fn ($record) => $this->getOwnerRecord()->total_remaining + $record->amount,
                            $this->getOwnerRecord()->advice_date ?? $this->getOwnerRecord()->invoice_date
                        )
                    )
                    ->visible(fn (RemittancePayment $record): bool => ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        RemittanceAdvice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                DeleteAction::make()->label('')
                    ->visible(fn (RemittancePayment $record): bool => ! $record->trashed())
                    ->before(function ($record) {
                        RemittanceAdvice::updateStatusOnDeleteChild($record);
                    })
                    ->after(function ($livewire) {
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
                    ->url(fn (RemittancePayment $record): string => RemittanceAdviceResource::getUrl('payment-activities', ['record' => $record]))
                    ->visible(fn (RemittancePayment $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
