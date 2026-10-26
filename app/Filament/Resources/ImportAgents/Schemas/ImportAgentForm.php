<?php

namespace App\Filament\Resources\ImportAgents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ImportAgentForm
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
                        ->label('Company Name')
                        ->required()
                        ->maxLength(256),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(128),
                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(128),
                    TextInput::make('fax')
                        ->label('Fax')
                        ->maxLength(128),
                    TextInput::make('address')
                        ->label('Address')
                        ->maxLength(256)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
