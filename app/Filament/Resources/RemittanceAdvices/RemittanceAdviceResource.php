<?php

namespace App\Filament\Resources\RemittanceAdvices;

use App\Filament\Resources\RemittanceAdvices\Pages\CreateRemittanceAdvice;
use App\Filament\Resources\RemittanceAdvices\Pages\EditRemittanceAdvice;
use App\Filament\Resources\RemittanceAdvices\Pages\ListRemittanceAdviceActivities;
use App\Filament\Resources\RemittanceAdvices\Pages\ListRemittanceAdviceItemActivities;
use App\Filament\Resources\RemittanceAdvices\Pages\ListRemittanceAdvicePaymentActivities;
use App\Filament\Resources\RemittanceAdvices\Pages\ListRemittanceAdvices;
use App\Filament\Resources\RemittanceAdvices\Pages\ViewRemittanceAdvice;
use App\Filament\Resources\RemittanceAdvices\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\RemittanceAdvices\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\RemittanceAdvices\Schemas\RemittanceAdviceForm;
use App\Filament\Resources\RemittanceAdvices\Tables\RemittanceAdvicesTable;
use App\Models\RemittanceAdvice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Table;

class RemittanceAdviceResource extends Resource
{
    protected static ?string $model = RemittanceAdvice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Invoices';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Remittance Advices';

    protected static ?string $modelLabel = 'Remittance Advice';

    protected static ?string $pluralModelLabel = 'Remittance Advices';

    protected static ?int $navigationSort = 8;

    public static function table(Table $table): Table
    {
        return RemittanceAdvicesTable::configure($table);
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
            'index' => ListRemittanceAdvices::route('/'),
            'create' => CreateRemittanceAdvice::route('/create'),
            'view' => ViewRemittanceAdvice::route('/{record}/view'),
            'edit' => EditRemittanceAdvice::route('/{record}/edit'),
            'activities' => ListRemittanceAdviceActivities::route('/{record}/activities'),
            'item-activities' => ListRemittanceAdviceItemActivities::route('/items/{record}/activities'),
            'payment-activities' => ListRemittanceAdvicePaymentActivities::route('/payments/{record}/activities'),
        ];
    }

    public static function getSteps(?int $masterBillId = null): array
    {
        return [
            Step::make('Remittance Advice Details')
                ->schema(RemittanceAdviceForm::components($masterBillId)),
            Step::make('Remittance Items')
                ->schema(RemittanceAdviceForm::getAdviceItemsSchema()),
        ];
    }

    public static function actions(bool $hideView = false): array
    {
        return [
            ViewAction::make()->label('')
                ->visible(! $hideView),
            EditAction::make()->label('')
                ->visible(function (RemittanceAdvice $record) {
                    return RemittanceAdvice::showActions($record) && ! $record->trashed();
                }),
            DeleteAction::make()->label('')
                ->recordTitle(fn ($record) => 'Remittance Advice ('.$record->advice_number.')')
                ->visible(function (RemittanceAdvice $record) {
                    return RemittanceAdvice::showActions($record) && ! $record->trashed();
                }),
            RestoreAction::make()->label(''),
            ForceDeleteAction::make()->label(''),
            Action::make('print')
                ->label('')
                ->icon('heroicon-o-printer')
                ->url(fn (RemittanceAdvice $record): string => route('remittance-advices.print', $record))
                ->openUrlInNewTab()
                ->authorize('print'),
            Action::make('activities')
                ->label('')
                ->icon('heroicon-o-document-text')
                ->visible(fn ($record) => ! $record->trashed())
                ->url(fn ($record) => RemittanceAdviceResource::getUrl('activities', ['record' => $record])),
        ];
    }
}
