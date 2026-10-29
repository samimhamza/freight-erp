<?php

namespace App\Filament\Resources\MasterBills\Pages;

use App\Filament\Resources\MasterBills\MasterBillResource;
use App\Models\MasterBill;
use App\Services\SendArrivalNotice;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ViewMasterBill extends ViewRecord
{
    protected static string $resource = MasterBillResource::class;

    protected $listeners = ['refreshViewMasterBill' => '$refresh'];

    protected function resolveRecord(int|string $key): Model
    {
        $model = static::getResource()::getModel();

        return $model::withTrashed()->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Master Bill')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('master-bills.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->authorize('print'),
            Action::make('sendArrivalNotices')
                ->label('Send Arrival Notices')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(function (MasterBill $record): string {
                    $alreadySentCount = $record->houseBills()
                        ->whereNull('deleted_at')
                        ->whereNotNull('arrival_notice_sent_at')
                        ->count();

                    return $alreadySentCount > 0 ? 'Send Arrival Notices Again?' : 'Send Arrival Notices';
                })
                ->modalIcon(function (MasterBill $record): string {
                    $alreadySentCount = $record->houseBills()
                        ->whereNull('deleted_at')
                        ->whereNotNull('arrival_notice_sent_at')
                        ->count();

                    return $alreadySentCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-paper-airplane';
                })
                ->modalDescription(function (MasterBill $record): Htmlable|string {
                    $eligibleHouseBills = $record->houseBills()
                        ->whereNull('deleted_at')
                        ->whereHas('customer', fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''))
                        ->get();

                    $eligibleCount = $eligibleHouseBills->count();
                    $alreadySentCount = $eligibleHouseBills->whereNotNull('arrival_notice_sent_at')->count();

                    if ($alreadySentCount > 0) {
                        return new HtmlString(
                            '<div class="space-y-3 text-left">'.
                            '<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-lg text-amber-800 dark:text-amber-200 text-sm font-medium flex items-center gap-2">'.
                            '<span>⚠️</span>'.
                            '<span><strong>Warning:</strong> '.$alreadySentCount.' of '.$eligibleCount.' customer(s) under this master bill have already received an arrival notice.</span>'.
                            '</div>'.
                            '<p class="text-sm text-gray-700 dark:text-gray-300">Send arrival notice emails to customers for '.$eligibleCount.' house bill(s) under this master bill? Each customer will receive only their own shipment and container details.</p>'.
                            '<p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Do you want to send arrival notices to them again?</p>'.
                            '</div>'
                        );
                    }

                    return "Send arrival notice emails to customers for {$eligibleCount} house bill(s) under this master bill? Each customer will receive only their own shipment and container details.";
                })
                ->visible(fn (MasterBill $record): bool => $record->canSendArrivalNotices())
                ->authorize('sendArrivalNotices')
                ->action(function (MasterBill $record, SendArrivalNotice $sendArrivalNotice): void {
                    $houseBills = $record->houseBills()
                        ->whereNull('deleted_at')
                        ->whereHas('customer', fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''))
                        ->with(['customer', 'masterBill'])
                        ->get();

                    $sent = 0;
                    foreach ($houseBills as $houseBill) {
                        $sendArrivalNotice->send($houseBill);
                        $sent++;
                    }

                    Notification::make()
                        ->title('Arrival notices sent')
                        ->body("Successfully sent arrival notices for {$sent} house bill(s).")
                        ->success()
                        ->send();
                }),
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Master Bill ('.$record->ocean_bill_of_lading.')'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema(self::schema());
    }

    public static function schema(): array
    {
        return [
            Section::make('Console Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('console')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('date')
                        ->dateTime('Y-m-d h:i A')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('ocean_bill_of_lading')
                        ->label('Ocean Bill of Lading')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('vessel.name')
                        ->formatStateUsing(function ($state, $record) {
                            return $state.' / '.$record->vessel->voyage;
                        }),
                    TextEntry::make('origin.code')
                        ->formatStateUsing(function ($state, $record) {
                            return $state.' ('.$record->origin->name.')';
                        }),
                    TextEntry::make('destination.code')
                        ->formatStateUsing(function ($state, $record) {
                            return $state.' ('.$record->destination->name.')';
                        }),
                    TextEntry::make('portOfDischarge.code')
                        ->label('Port of Discharge')
                        ->formatStateUsing(function ($state, $record) {
                            return $state.' ('.$record->portOfDischarge->name.')';
                        })
                        ->visible(fn ($state) => $state),
                    TextEntry::make('etd')
                        ->label('ETD (Estimated Time of Departure)')
                        ->date()
                        ->visible(fn ($state) => $state),
                    TextEntry::make('eta')
                        ->label('ETA (Estimated Time of Arrival)')
                        ->date()
                        ->visible(fn ($state) => $state),
                    TextEntry::make('ata')
                        ->label('ATA (Actual Time of Arrival)')
                        ->date()
                        ->visible(fn ($state) => $state),
                    TextEntry::make('total_containers')
                        ->label('Total Containers'),
                    TextEntry::make('total_vehicles')
                        ->label('Total Vehicles'),
                    TextEntry::make('total_packages')
                        ->label('Total Packages'),
                    TextEntry::make('total_weight')
                        ->label('Total Weight')
                        ->suffix('KG'),
                    TextEntry::make('total_volume')
                        ->label('Total Volume')
                        ->suffix('M3'),
                    TextEntry::make('carrier.name')
                        ->label('Carrier')
                        ->formatStateUsing(function ($state, $record) {
                            return $record->carrier?->code ? "{$record->carrier->name} ({$record->carrier->code})" : $record->carrier?->name;
                        })
                        ->visible(fn ($state) => $state),
                    TextEntry::make('exportAgent.name')
                        ->label('Export Agent')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('importAgent.name')
                        ->label('Import Agent')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('custom_entry_number')
                        ->label('Custom Entry Number')
                        ->visible(fn ($state) => $state),
                    TextEntry::make('master_frieght')
                        ->label('Master Frieght')
                        ->visible(fn ($state) => $state),
                    ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                ])->columns(3),
        ];
    }
}
