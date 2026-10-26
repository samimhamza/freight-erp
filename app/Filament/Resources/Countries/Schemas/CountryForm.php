<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CountryForm
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
                    TextInput::make('name')
                        ->required()
                        ->maxLength(128)
                        ->unique(table: 'countries', ignoreRecord: true)->live(onBlur: true),
                    TextInput::make('code')
                        ->required()
                        ->maxLength(32)
                        ->unique(table: 'countries', ignoreRecord: true)->live(onBlur: true),
                ]),
        ];
    }
}
