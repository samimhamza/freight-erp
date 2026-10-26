<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\SendInvoiceToCustomer;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected $listeners = ['refreshViewPage' => '$refresh'];

    protected function resolveRecord(int|string $key): Model
    {
        $model = static::getResource()::getModel();

        return $model::withTrashed()
            ->with(['customer', 'houseBill.containers.container'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Invoice $record) => Invoice::showActions($record) && ! $record->trashed()),
            Action::make('sendToCustomer')
                ->label('Send to Customer')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send invoice to customer')
                ->modalDescription(function (Invoice $record): string {
                    $email = $record->customer?->email ?? 'the customer';

                    return $record->status === InvoiceStatus::DRAFT
                        ? "The invoice PDF will be emailed to {$email}. The invoice status will be updated to Sent."
                        : "The invoice PDF will be emailed to {$email}.";
                })
                ->visible(fn (Invoice $record): bool => $record->canBeSentToCustomer())
                ->authorize('send')
                ->action(function (Invoice $record, SendInvoiceToCustomer $sendInvoiceToCustomer): void {
                    try {
                        $markedAsSent = $sendInvoiceToCustomer->send($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to send invoice')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'The invoice could not be sent.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Invoice sent')
                        ->body($markedAsSent
                            ? 'The invoice was emailed to the customer and marked as Sent.'
                            : 'The invoice was emailed to the customer.')
                        ->send();

                    $this->dispatch('refreshChildRelations');
                    $this->dispatch('refreshViewPage');
                }),
            Action::make('print')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('invoices.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->authorize('print'),
            DeleteAction::make()
                ->visible(Invoice::showActions($this->getRecord())),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Invoice Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('invoice_number'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('customer.name')
                            ->placeholder('—'),
                        TextEntry::make('house_bill')
                            ->label('House B/L')
                            ->state(fn (Invoice $record): ?string => $record->house_bill_number)
                            ->placeholder('—'),
                        TextEntry::make('containers')
                            ->label('Containers')
                            ->state(fn (Invoice $record): string => implode(', ', $record->all_container_numbers) ?: '—')
                            ->badge()
                            ->separator(',')
                            ->placeholder('—'),
                        TextEntry::make('invoice_date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => Invoice::getStatusColor($state))
                            ->visible(function (Invoice $record) {
                                return $record->status !== InvoiceStatus::DRAFT && $record->status !== InvoiceStatus::CANCELLED;
                            }),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => Invoice::getStatusColor($state))
                            ->action(
                                Action::make('status')
                                    ->schema([
                                        Select::make('status')
                                            ->options(Invoice::getChangableStatuses())
                                            ->required(),
                                    ])
                                    ->action(function (Invoice $record, array $data, $livewire) {
                                        $record->update([
                                            'status' => $data['status'],
                                        ]);
                                        $livewire->dispatch('refreshChildRelations');
                                    })->modalHeading('Change Status')
                            )
                            ->visible(function (Invoice $record) {
                                return $record->status === InvoiceStatus::DRAFT || $record->status === InvoiceStatus::CANCELLED;
                            }),
                        TextEntry::make('total')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' AED')
                            ->default(0),
                        TextEntry::make('total_paid')
                            ->label('Total Paid')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' AED')
                            ->default(0),
                        TextEntry::make('total_remaining')
                            ->label('Total Remaining')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' AED')
                            ->default(0),
                        ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                    ])->columns(3),
            ]);
    }
}
