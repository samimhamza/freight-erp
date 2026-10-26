<?php

namespace App\Filament\Resources\RemittanceAdvices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Models\RemittanceAdvice;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RemittanceAdvicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions(RemittanceAdviceResource::actions())
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('advice_number')
                ->label('Advice Number')
                ->searchable()
                ->sortable(),
            TextColumn::make('carrier.name')
                ->label('Carrier')
                ->searchable()
                ->sortable(),
            TextColumn::make('masterBill.console')
                ->label('Console')
                ->searchable()
                ->sortable(),
            TextColumn::make('masterBill.ocean_bill_of_lading')
                ->label('Ocean B/L')
                ->searchable()
                ->sortable(),
            TextColumn::make('advice_date')
                ->label('Advice Date')
                ->date()
                ->sortable(),
            TextColumn::make('due_date')
                ->date()
                ->sortable(),
            TextColumn::make('total')
                ->numeric(decimalPlaces: 2)
                ->suffix(' AED')
                ->sortable(),
            TextColumn::make('status')
                ->searchable()
                ->badge()
                ->color(fn ($state) => RemittanceAdvice::getStatusColor($state)),
            ...HasTimestampsAndUsers::timestampsWithUsers(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('carrier')
                ->relationship('carrier', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('masterBill')
                ->relationship('masterBill', 'ocean_bill_of_lading')
                ->searchable()
                ->preload(),
            SelectFilter::make('status')
                ->multiple()
                ->options(InvoiceStatus::class),
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:RemittanceAdvice') || auth()->user()->can('ForceDelete:RemittanceInvoice') || auth()->user()->can('ForceDelete:PayableInvoice') || auth()->user()->can('ForceDelete:Invoice')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
