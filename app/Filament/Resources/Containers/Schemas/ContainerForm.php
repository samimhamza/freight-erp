<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Enums\ContainerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContainerForm
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
                    TextInput::make('container_number')
                        ->label('Container Number')
                        ->maxLength(32)
                        ->unique(table: 'containers', ignoreRecord: true)->live(onBlur: true)
                        ->required(),
                    Select::make('type')
                        ->label('Container Type')
                        ->options([
                            ContainerType::HC40->value => '40HC',
                            ContainerType::HC45->value => '45HC',
                        ])
                        ->required(),
                    TextInput::make('tare_weight')
                        ->label('Tare Weight')
                        ->suffix('Kg')
                        ->numeric()
                        ->inputMode('decimal'),
                    TextInput::make('volume')
                        ->label('Volume')
                        ->required()
                        ->suffix('M3')
                        ->numeric()
                        ->inputMode('decimal'),
                ]),
        ];
    }
}
