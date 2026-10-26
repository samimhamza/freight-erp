<?php

namespace App\Filament\Resources\Carriers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CarrierForm
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
                        ->label('Carrier Name')
                        ->required()
                        ->unique(table: 'carriers', ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Code')
                        ->unique(table: 'carriers', ignoreRecord: true)
                        ->maxLength(32),
                    TextInput::make('account_number')
                        ->label('Payee Account Number / Bank Code')
                        ->maxLength(64)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
