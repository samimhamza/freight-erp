<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                ViewAction::make()->label('')
                    ->recordTitle(fn ($record) => 'User ('.$record->first_name.' '.$record->last_name.')')
                    ->schema([
                        Grid::make(3)->schema([
                            SpatieMediaLibraryImageEntry::make('avatar')
                                ->collection('user_avatars')
                                ->visible(fn ($state) => $state),
                            TextEntry::make('first_name'),
                            TextEntry::make('last_name'),
                            TextEntry::make('username'),
                            TextEntry::make('phone'),
                            TextEntry::make('email'),
                            TextEntry::make('email_verified_at'),
                            Grid::make(1)->schema([
                                RepeatableEntry::make('roles')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->badge(),
                                    ])
                                    ->grid(2)
                                    ->columns(1),
                            ]),
                            ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                        ]),
                    ]),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label(label: '')
                    ->recordTitle(fn (User $record) => 'User ('.$record->first_name.' '.$record->last_name.')')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id() || ($record->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin'))),
                RestoreAction::make()
                    ->visible(fn (User $record): bool => $record->trashed() && (! $record->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))),
                ForceDeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->id === auth()->id() || ($record->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin'))),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => UserResource::getUrl('activities', ['record' => $record])),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            SpatieMediaLibraryImageColumn::make('avatar')
                ->collection('user_avatars'),
            TextColumn::make('first_name')
                ->searchable(),
            TextColumn::make('last_name')
                ->searchable(),
            TextColumn::make('username')
                ->searchable(),
            TextColumn::make('phone')
                ->searchable(),
            TextColumn::make('email')
                ->searchable(),
            TextColumn::make('email_verified_at')
                ->dateTime()
                ->sortable(),
            ...HasTimestampsAndUsers::timestampsWithUsers(false, showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:User')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
