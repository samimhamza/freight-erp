<?php

namespace App\Filament\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoiceActivities;
use App\Filament\Resources\Invoices\Pages\ListInvoiceItemActivities;
use App\Filament\Resources\Invoices\Pages\ListInvoicePaymentActivities;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Invoices';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 7;

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}/view'),
            'edit' => EditInvoice::route('/{record}/edit'),
            'activities' => ListInvoiceActivities::route('/{record}/activities'),
            'item-activities' => ListInvoiceItemActivities::route('/items/{record}/activities'),
            'payment-activities' => ListInvoicePaymentActivities::route('/payments/{record}/activities'),
        ];
    }

    public static function getSteps(?int $houseBillId = null, ?int $customerId = null): array
    {
        return [
            Step::make('Invoice Details')
                ->schema(InvoiceForm::components($houseBillId, $customerId)),
            Step::make('Invoice Items')
                ->schema(InvoiceForm::getInvoiceItemsSchema()),
        ];
    }

    public static function configureDeleteAction(DeleteAction $action): DeleteAction
    {
        return $action
            ->recordTitle(fn ($record) => "Record ({$record?->invoice_number})")
            ->visible(fn ($record): bool => ! ($record?->trashed() ?? false))
            ->modal(fn ($record): bool => (bool) ($record?->payments()->exists() ?? false))
            ->requiresConfirmation(fn ($record): bool => (bool) ($record?->payments()->exists() ?? false))
            ->modalHeading('Delete')
            ->modalDescription('This record has already payments, do you really want to delete it?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger');
    }

    public static function actions(bool $hideView = false): array
    {
        return [
            ViewAction::make()->label('')
                ->visible(! $hideView),
            EditAction::make()->label('')
                ->visible(function (Invoice $record) {
                    return Invoice::showActions($record) && ! $record->trashed();
                }),
            self::configureDeleteAction(DeleteAction::make()->label('')),
            Action::make('cancel')
                ->label('')
                ->tooltip('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel')
                ->modalDescription(fn ($record): string => 'Are you sure you want to cancel this record? This will update its status to Cancelled.')
                ->visible(fn ($record): bool => in_array($record->status, [InvoiceStatus::DRAFT, InvoiceStatus::SENT], true) && ! $record->trashed())
                ->authorize('cancel')
                ->action(function ($record): void {
                    $record->update(['status' => InvoiceStatus::CANCELLED]);
                    Notification::make()->success()->title('Cancelled successfully')->send();
                }),
            RestoreAction::make()->label(''),
            ForceDeleteAction::make()->label(''),
            Action::make('print')
                ->label('')
                ->icon('heroicon-o-printer')
                ->url(fn (Invoice $record): string => route('invoices.print', $record))
                ->openUrlInNewTab()
                ->authorize('print'),
            Action::make('activities')
                ->label('')
                ->icon('heroicon-o-document-text')
                ->visible(fn ($record) => ! $record->trashed())
                ->url(fn ($record) => InvoiceResource::getUrl('activities', ['record' => $record])),
        ];
    }
}
