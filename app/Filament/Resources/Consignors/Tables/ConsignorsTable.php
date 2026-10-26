<?php

namespace App\Filament\Resources\Consignors\Tables;

use App\Filament\Resources\Consignors\ConsignorResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ConsignorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                ViewAction::make()->label('')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name'),
                            TextEntry::make('email')
                                ->visible(fn ($state) => $state),
                            TextEntry::make('phone')
                                ->visible(fn ($state) => $state),
                            TextEntry::make('fax')
                                ->visible(fn ($state) => $state),
                            ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                            TextEntry::make('address')
                                ->columnSpanFull()
                                ->visible(fn ($state) => $state),
                            TextEntry::make('country.name')
                                ->formatStateUsing(fn ($record) => "{$record->country->name} ({$record->country->code})"),
                        ]),
                    ]),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label(''),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => ConsignorResource::getUrl('activities', ['record' => $record])),
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
            ...HasTimestampsAndUsers::timestampsWithUsers(toggleable: false, showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:Consignor')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
