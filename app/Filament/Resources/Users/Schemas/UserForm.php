<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->image()
                        ->imageEditor()
                        ->collection('user_avatars')
                        ->columnSpanFull(),
                    TextInput::make('first_name')
                        ->required()
                        ->maxLength(64),
                    TextInput::make('last_name')
                        ->required()
                        ->maxLength(64),
                    TextInput::make('username')
                        ->required()
                        ->maxLength(64),
                    TextInput::make('phone')
                        ->tel()
                        ->required()
                        ->maxLength(length: 16),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(64),
                    TextInput::make('password')
                        ->password()
                        ->required()
                        ->maxLength(32)
                        ->visibleOn('create'),
                    Select::make('roles')
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => auth()->user()?->hasRole('super_admin')
                                ? $query
                                : $query->where('name', '!=', 'super_admin')
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull()
                        ->disabled(fn (?User $record): bool => $record !== null && $record->id === auth()->id() && $record->hasRole('super_admin'))
                        ->dehydrated(fn (?User $record): bool => ! ($record !== null && $record->id === auth()->id() && $record->hasRole('super_admin')))
                        ->helperText(fn (?User $record): ?string => $record !== null && $record->id === auth()->id() && $record->hasRole('super_admin')
                            ? 'You cannot remove the super_admin role from your own account.'
                            : null
                        ),
                ]),
        ];
    }
}
