<?php

namespace App\Filament\Resources\RemittanceAdvices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Models\RemittanceAdvice;
use App\Services\SendRemittanceAdvice;
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

class ViewRemittanceAdvice extends ViewRecord
{
    protected static string $resource = RemittanceAdviceResource::class;

    protected $listeners = ['refreshViewPage' => '$refresh'];

    protected function resolveRecord(int|string $key): Model
    {
        $model = static::getResource()::getModel();

        return $model::withTrashed()
            ->with(['masterBill.vessel', 'carrier'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (RemittanceAdvice $record) => RemittanceAdvice::showActions($record) && ! $record->trashed()),
            Action::make('sendToAccounts')
                ->label('Send to Accounts')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send remittance advice to accounts')
                ->modalDescription(function (RemittanceAdvice $record): string {
                    $email = config('company.remittance_advice_email') ?? config('company.remittance_invoice_email') ?? config('company.payable_invoice_email') ?? 'accounts';

                    return $record->status === InvoiceStatus::DRAFT
                        ? "The remittance advice PDF will be emailed to {$email}. The advice status will be updated to Sent."
                        : "The remittance advice PDF will be emailed to {$email}.";
                })
                ->visible(fn (RemittanceAdvice $record): bool => $record->canBeSent())
                ->authorize('send')
                ->action(function (RemittanceAdvice $record, SendRemittanceAdvice $sendRemittanceAdvice): void {
                    try {
                        $markedAsSent = $sendRemittanceAdvice->send($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to send remittance advice')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'The remittance advice could not be sent.')
                            ->send();

                        return;
                    }

                    $email = config('company.remittance_advice_email') ?? config('company.remittance_invoice_email') ?? config('company.payable_invoice_email');
                    Notification::make()
                        ->success()
                        ->title('Remittance advice sent')
                        ->body($markedAsSent
                            ? "The remittance advice was emailed to {$email} and marked as Sent."
                            : "The remittance advice was emailed to {$email}.")
                        ->send();

                    $this->dispatch('refreshChildRelations');
                    $this->dispatch('refreshViewPage');
                }),
            Action::make('print')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('remittance-advices.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->authorize('print'),
            DeleteAction::make()
                ->visible(RemittanceAdvice::showActions($this->getRecord())),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Remittance Advice Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('advice_number')
                            ->label('Advice Number'),
                        TextEntry::make('carrier.name')
                            ->label('Carrier'),
                        TextEntry::make('masterBill.console')
                            ->label('Console'),
                        TextEntry::make('masterBill.ocean_bill_of_lading')
                            ->label('Ocean Bill of Lading'),
                        TextEntry::make('advice_date')
                            ->label('Advice Date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => RemittanceAdvice::getStatusColor($state))
                            ->visible(function (RemittanceAdvice $record) {
                                return $record->status !== InvoiceStatus::DRAFT && $record->status !== InvoiceStatus::CANCELLED;
                            }),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => RemittanceAdvice::getStatusColor($state))
                            ->action(
                                Action::make('status')
                                    ->schema([
                                        Select::make('status')
                                            ->options(RemittanceAdvice::getChangableStatuses())
                                            ->required(),
                                    ])
                                    ->action(function (RemittanceAdvice $record, array $data, $livewire) {
                                        $record->update([
                                            'status' => $data['status'],
                                        ]);
                                        $livewire->dispatch('refreshChildRelations');
                                    })->modalHeading('Change Status')
                            )
                            ->visible(function (RemittanceAdvice $record) {
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
