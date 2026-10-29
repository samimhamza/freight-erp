<?php

namespace App\Filament\Resources\HouseBills\Tables;

use App\Filament\Resources\HouseBills\HouseBillResource;
use App\Models\HouseBill;
use App\Services\SendArrivalNotice;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class HouseBillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'masterBill',
                'customer',
                'consignor',
                'notifyParty',
                'containers.container',
                'containers.vehicles',
            ]))
            ->columns(self::columns())
            ->filters(self::filters())
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
                    ->action(function (HouseBill $record, SendArrivalNotice $sendArrivalNotice): void {
                        $sendArrivalNotice->send($record);

                        Notification::make()
                            ->title('Arrival notice sent successfully')
                            ->body("Arrival notice for {$record->house_bill} sent to {$record->customer->email}")
                            ->success()
                            ->send();
                    }),
                ViewAction::make()->label(''),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label('')
                    ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')'),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->using(function ($record): bool {
                        $reasons = $record->getForceDeleteRestrictions();

                        if (! empty($reasons)) {
                            Notification::make()
                                ->warning()
                                ->title('Cannot Permanently Delete')
                                ->body('Cannot be permanently deleted because it still has associated '.implode(' and ', $reasons).'. Please delete them first.')
                                ->persistent()
                                ->send();

                            return false;
                        }

                        try {
                            return (bool) $record->forceDelete();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title('Deletion failed')->send();

                            return false;
                        }
                    }),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => HouseBillResource::getUrl('activities', ['record' => $record])),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(bool $showMasterBill = true): array
    {
        return [
            TextColumn::make('masterBill.ocean_bill_of_lading')
                ->sortable()
                ->visible($showMasterBill),
            TextColumn::make('house_bill')
                ->searchable(),
            TextColumn::make('consignor.name')
                ->label('Consignor Name')
                ->searchable(),
            TextColumn::make('consignor.email')
                ->label('Consignor Email')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('consignor.phone')
                ->label('Consignor Phone')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('customer.name')
                ->label('Customer Name')
                ->searchable(),
            TextColumn::make('customer.email')
                ->label('Customer Email')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('customer.phone')
                ->label('Customer Phone')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('notifyParty.name')
                ->label('Notify Party Name')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('notifyParty.email')
                ->label('Notify Party Email')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('notifyParty.phone')
                ->label('Notify Party Phone')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('containers_count')
                ->counts('containers')
                ->label('Total Containers'),
            TextColumn::make('total_packages')
                ->label('Total Packages'),
            TextColumn::make('total_vehicles')
                ->label('Total Vehicles'),
            TextColumn::make('total_weight')
                ->label('Total Weight'),
            TextColumn::make('total_volume')
                ->label('Total volume'),
            TextColumn::make('arrival_notice_sent_at')
                ->label('Notice Sent')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ...HasTimestampsAndUsers::timestampsWithUsers(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('masterBill')
                ->relationship('masterBill', 'ocean_bill_of_lading')
                ->searchable()
                ->preload(),
            SelectFilter::make('customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload(),
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:HouseBill')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
