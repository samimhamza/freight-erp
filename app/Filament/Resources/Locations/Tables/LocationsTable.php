<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Filament\Resources\Locations\LocationResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label(label: ''),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => LocationResource::getUrl('activities', ['record' => $record])),
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
                ->searchable(),
            TextColumn::make('code')
                ->searchable()
                ->sortable(),
            ...HasTimestampsAndUsers::timestampsWithUsers(toggleable: false, showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:Location')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
