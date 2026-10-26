<?php

namespace App\Filament\Resources\HouseBills\Pages;

use App\Filament\Resources\HouseBills\HouseBillResource;
use App\Models\HouseBill;
use App\Models\Vehicle;
use App\Services\SendArrivalNotice;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ViewHouseBill extends ViewRecord
{
    protected static string $resource = HouseBillResource::class;

    protected $listeners = ['refreshViewHouseBill' => '$refresh'];

    protected function resolveRecord(int|string $key): Model
    {
        $model = static::getResource()::getModel();

        return $model::withTrashed()
            ->with(['customer', 'masterBill', 'consignor', 'notifyParty'])
            ->withCount('containers')
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print House Bill')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('house-bills.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->authorize('print'),
            Action::make('arrivalNotice')
                ->label('Print Arrival Notice')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->url(fn (): string => route('house-bills.arrival-notice', $this->getRecord()))
                ->openUrlInNewTab()
                ->visible(fn (HouseBill $record): bool => $record->loadMissing('masterBill')->canPrintArrivalNotice())
                ->authorize('arrivalNotice'),
            Action::make('sendArrivalNotice')
                ->label('Send Arrival Notice')
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
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'House Bill ('.$record->house_bill.')'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema(self::schema());
    }

    public static function schema($showVehicles = false)
    {
        return [
            Section::make('House Bill Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('consignor.name'),
                    TextEntry::make('customer.name'),
                    TextEntry::make('masterBill.ocean_bill_of_lading')
                        ->label('Master'),
                    TextEntry::make('house_bill'),
                    TextEntry::make('total_containers')
                        ->label('Total Containers')
                        ->state(fn (HouseBill $record): int => (int) $record->total_containers),
                    TextEntry::make('total_packages'),
                    TextEntry::make('total_vehicles'),
                    TextEntry::make('total_weight'),
                    TextEntry::make('total_volume'),
                    TextEntry::make('arrival_notice_sent_at')
                        ->label('Arrival Notice Sent At')
                        ->dateTime()
                        ->visible(fn ($state) => $state),
                    TextEntry::make('goods_description')
                        ->visible(fn ($state) => $state),

                    ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                ])
                ->columns(3),
            Section::make('Container Details')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('containers')
                        ->label(fn ($state) => 'Containers ('.count($state).')')
                        ->schema([
                            TextEntry::make('container.container_number')
                                ->label('Container Number'),
                            TextEntry::make('container.type')
                                ->label('Container Type'),
                            TextEntry::make('container.volume')
                                ->label('Volume')
                                ->suffix(' M3'),
                            TextEntry::make('seal')
                                ->label('Seal'),
                            TextEntry::make('container.tare_weight')
                                ->label('Tare Weight')
                                ->suffix('KG')
                                ->visible(fn ($state) => $state),
                            TextEntry::make('net_weight')
                                ->label('Net Weight')
                                ->suffix('KG'),
                            TextEntry::make('gross_weight')
                                ->label('Gross Weight')
                                ->suffix('KG'),
                            TextEntry::make('packages'),
                            Grid::make(1)
                                ->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('vehicles')
                                        ->label(fn ($state) => 'Vehicles ('.count($state).')')
                                        ->schema([
                                            TextEntry::make('vin'),
                                            TextEntry::make('year'),
                                            TextEntry::make('make'),
                                            TextEntry::make('model'),
                                            TextEntry::make('color'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn ($state) => Vehicle::getStatusColor($state)),
                                            TextEntry::make('type')
                                                ->badge()
                                                ->color(fn ($state) => Vehicle::getTypeColor($state)),
                                        ])->grid(1)
                                        ->columns(3)
                                        ->visible(fn ($state) => $state),
                                ]),
                        ])->grid(1)
                        ->columns(3)
                        ->visible(fn ($state) => $state),
                ])
                ->columns(1),

        ];
    }
}
