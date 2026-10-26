<?php

namespace App\Filament\Resources\Charges\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(64)
                ->unique(table: 'charges', ignoreRecord: true)->live(onBlur: true)
                ->columnSpanFull(),
        ];
    }
}
