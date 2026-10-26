<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                ViewAction::make()->label(''),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label('')
                    ->recordTitle(fn ($record) => 'Customer ('.$record->name.')'),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => CustomerResource::getUrl('activities', ['record' => $record])),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->searchable()
                ->sortable(),
            TextColumn::make('phone')
                ->searchable()
                ->sortable(),
            TextColumn::make('fax'),
            TextColumn::make('address'),
            TextColumn::make('country.name')
                ->searchable()
                ->sortable()
                ->formatStateUsing(fn ($record) => "{$record->country->name} ({$record->country->code})"),
            ...HasTimestampsAndUsers::timestampsWithUsers(false, showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:Customer')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
