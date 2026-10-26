<?php

namespace App\Traits;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

trait HasTimestampsAndUsers
{
    public static function timestampsWithUsers($toggleable = true, $showDeleted = false): array
    {
        return [
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: $toggleable),
            TextColumn::make('createdBy.first_name')->sortable()->searchable()
                ->label('Created by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->createdBy->last_name;
                })
                ->toggleable(isToggledHiddenByDefault: $toggleable),
            TextColumn::make('updated_at')
                ->label('Last updated at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: $toggleable),
            TextColumn::make('updatedBy.first_name')->sortable()->searchable()
                ->label('Last updated by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->updatedBy->last_name;
                })
                ->toggleable(isToggledHiddenByDefault: $toggleable),
            TextColumn::make('deleted_at')
                ->label('Deleted at')
                ->dateTime()
                ->sortable()
                ->visible($showDeleted),
            TextColumn::make('deletedBy.first_name')->sortable()->searchable()
                ->label('Deleted by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->deletedBy->last_name;
                })
                ->visible($showDeleted),
        ];
    }

    public static function timestampsWithUsersInfolist(): array
    {
        return [
            TextEntry::make('created_at')
                ->dateTime('Y-m-d h:i A'),
            TextEntry::make('createdBy.first_name')
                ->label('Created by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->createdBy->last_name;
                })
                ->visible(fn($state) => $state),
            TextEntry::make('updated_at')
                ->label('Last updated at')
                ->dateTime('Y-m-d h:i A'),
            TextEntry::make('updatedBy.first_name')
                ->label('Last updated by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->updatedBy->last_name;
                })
                ->visible(fn($state) => $state),
            TextEntry::make('deleted_at')
                ->label('Deleted at')
                ->dateTime('Y-m-d h:i A')
                ->visible(fn($state): mixed => $state),
            TextEntry::make('deletedBy.first_name')
                ->label('Deleted by')
                ->formatStateUsing(function ($state, $record) {
                    return $state . ' ' . $record->deletedBy->last_name;
                })
                ->visible(fn($state): mixed => $state),
        ];
    }

    public static function showDeleted($livewire)
    {
        $trashed = $livewire->getTableFilterState('trashed');
        if ($trashed && ($trashed['value'] === '0' || $trashed['value'] === '1')) {
            return true;
        }
        return false;
    }

    public static function logFilters($showDeleted = false)
    {
        return [
            SelectFilter::make('createdBy')
                ->relationship('createdBy', 'first_name')
                ->searchable()
                ->preload(),
            SelectFilter::make('updatedBy')
                ->relationship('updatedBy', 'first_name')
                ->searchable()
                ->preload(),
            SelectFilter::make('deletedBy')
                ->relationship('deletedBy', 'first_name')
                ->searchable()
                ->preload()
                ->visible($showDeleted),

        ];
    }
}
