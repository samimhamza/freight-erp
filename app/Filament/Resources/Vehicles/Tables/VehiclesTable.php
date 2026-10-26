<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\Vehicle;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label(''),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => VehicleResource::getUrl('activities', ['record' => $record])),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('vin')
                ->searchable(),
            TextColumn::make('year')
                ->sortable(),
            TextColumn::make('make')
                ->searchable(),
            TextColumn::make('model')
                ->searchable(),
            TextColumn::make('color')
                ->searchable(),
            TextColumn::make('status')
                ->searchable()
                ->badge()
                ->color(fn ($state) => Vehicle::getStatusColor($state)),
            TextColumn::make('type')
                ->searchable()
                ->badge()
                ->color(fn ($state) => Vehicle::getTypeColor($state)),
            ...HasTimestampsAndUsers::timestampsWithUsers(toggleable: false, showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('status')
                ->options(VehicleStatus::class),
            SelectFilter::make('type')
                ->options(VehicleType::class),
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:Vehicle')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
