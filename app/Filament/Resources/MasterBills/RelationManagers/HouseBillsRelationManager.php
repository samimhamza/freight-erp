<?php

namespace App\Filament\Resources\MasterBills\RelationManagers;

use App\Filament\Resources\HouseBills\Pages\ViewHouseBill;
use App\Filament\Resources\HouseBills\Schemas\HouseBillForm;
use App\Filament\Resources\HouseBills\Tables\HouseBillsTable;
use App\Models\HouseBill;
use App\Services\SendArrivalNotice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class HouseBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'houseBills';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(HouseBillForm::components());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(HouseBillsTable::columns(false))
            ->filters(HouseBillsTable::filters())
            ->headerActions([
                CreateAction::make()
                    ->label('New House Bill')
                    ->icon('heroicon-o-plus')
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewMasterBill');
                    }),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('')
                    ->tooltip('Print House Bill')
                    ->icon('heroicon-o-printer')
                    ->url(fn (HouseBill $record): string => route('house-bills.print', $record))
                    ->openUrlInNewTab()
                    ->authorize('print'),
                Action::make('arrivalNotice')
                    ->label('')
                    ->tooltip('Print Arrival Notice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (HouseBill $record): string => route('house-bills.arrival-notice', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (HouseBill $record): bool => $record->loadMissing('masterBill')->canPrintArrivalNotice())
                    ->authorize('arrivalNotice'),
                Action::make('sendArrivalNotice')
                    ->label('')
                    ->tooltip('Send Arrival Notice')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (HouseBill $record): string => $record->arrival_notice_sent_at !== null ? 'Send Arrival Notice Again?' : 'Send Arrival Notice')
                    ->modalIcon(fn (HouseBill $record): string => $record->arrival_notice_sent_at !== null ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-paper-airplane')
                    ->modalDescription(function (HouseBill $record): Htmlable|string {
                        $customerInfo = "{$record->customer?->name} ({$record->customer?->email})";
                        if ($record->arrival_notice_sent_at !== null) {
                            $sentDate = $record->arrival_notice_sent_at->timezone('Asia/Dubai')->format('d M Y, h:i A');

                            return new HtmlString(
                                '<div class="space-y-3 text-left">'.
                                '<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-lg text-amber-800 dark:text-amber-200 text-sm font-medium flex items-center gap-2">'.
                                '<span>⚠️</span>'.
                                "<span>Customer has already received the arrival notice on <strong>{$sentDate}</strong>.</span>".
                                '</div>'.
                                "<p class=\"text-sm text-gray-700 dark:text-gray-300\">Do you want to send the arrival notice to <strong>{$customerInfo}</strong> again?</p>".
                                '</div>'
                            );
                        }

                        return "Send arrival notice email to {$customerInfo}?";
                    })
                    ->visible(fn (HouseBill $record): bool => $record->loadMissing(['customer', 'masterBill'])->canSendArrivalNotice())
                    ->authorize('sendArrivalNotice')
                    ->action(function (HouseBill $record, SendArrivalNotice $sendArrivalNotice, $livewire): void {
                        $sendArrivalNotice->send($record);

                        Notification::make()
                            ->title('Arrival notice sent successfully')
                            ->body("Arrival notice for {$record->house_bill} sent to {$record->customer->email}")
                            ->success()
                            ->send();

                        $livewire->dispatch('refreshViewMasterBill');
                    }),
                ViewAction::make()->label('')
                    ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')')
                    ->schema(ViewHouseBill::schema(true)),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')')
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewMasterBill');
                    }),
                DeleteAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')')
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewMasterBill');
                    }),
                RestoreAction::make()->label(''),
                ForceDeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
