<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
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
            ->columns(Payment::columns())
            ->filters([
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->can('ForceDelete:Invoice')),
                ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Payment')
                    ->icon('heroicon-o-plus')
                    ->schema(Payment::form(
                        $this->getOwnerRecord()->total_remaining,
                        $this->getOwnerRecord()->invoice_date
                    ))
                    ->mutateDataUsing(function (array $data): array {
                        $data['payment_number'] = $this->getOwnerRecord()->payments()->count() + 1;

                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    })
                    ->visible(Invoice::showCreatePayment($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()->label('')
                    ->schema(
                        Payment::form(
                            fn ($record) => $this->getOwnerRecord()->total_remaining + $record->amount,
                            $this->getOwnerRecord()->invoice_date
                        )
                    )
                    ->visible(fn (Payment $record): bool => ! $record->trashed())
                    ->after(function ($record, $livewire) {
                        Invoice::updateStatusOnSaveChild($record);
                        $livewire->dispatch('refreshViewPage');
                    }),
                DeleteAction::make()->label('')
                    ->visible(fn (Payment $record): bool => ! $record->trashed())
                    ->before(function ($record) {
                        Invoice::updateStatusOnDeleteChild($record);
                    })
                    ->after(function ($livewire) {
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
                    ->url(fn (Payment $record): string => InvoiceResource::getUrl('payment-activities', ['record' => $record]))
                    ->visible(fn (Payment $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
