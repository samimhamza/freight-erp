<?php

namespace App\Filament\Resources\Vessels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class VesselForm
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
                        ->maxLength(32),
                    TextInput::make('voyage')
                        ->required()
                        ->maxLength(32),
                ]),
        ];
    }
}
