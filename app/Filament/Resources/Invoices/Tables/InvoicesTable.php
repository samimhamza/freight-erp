<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions(InvoiceResource::actions())
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('invoice_number')
                ->searchable()
                ->sortable(),
            TextColumn::make('type')
                ->badge()
                ->sortable(),
            TextColumn::make('customer.name')
                ->searchable()
                ->sortable()
                ->placeholder('—'),
            TextColumn::make('house_bill')
                ->label('House B/L')
                ->state(fn (Invoice $record): ?string => $record->house_bill_number)
                ->searchable(query: function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('invoices.house_bill', 'like', "%{$search}%")
                            ->orWhereHas('houseBill', fn ($sub) => $sub->where('house_bills.house_bill', 'like', "%{$search}%"));
                    });
                })
                ->placeholder('—'),
            TextColumn::make('containers')
                ->label('Containers')
                ->state(fn (Invoice $record): string => implode(', ', $record->all_container_numbers) ?: '—')
                ->badge()
                ->separator(',')
                ->placeholder('—'),
            TextColumn::make('invoice_date')
                ->date()
                ->sortable(),
            TextColumn::make('due_date')
                ->date()
                ->sortable(),
            TextColumn::make('total')
                ->numeric()
                ->sortable(),
            TextColumn::make('status')
                ->searchable()
                ->badge()
                ->color(fn ($state) => Invoice::getStatusColor($state)),
            ...HasTimestampsAndUsers::timestampsWithUsers(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('type')
                ->options(InvoiceType::class),
            SelectFilter::make('houseBill')
                ->relationship('houseBill', 'house_bill')
                ->searchable()
                ->preload(),
            SelectFilter::make('customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('status')
                ->multiple()
                ->options(InvoiceStatus::class),
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:Invoice')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
