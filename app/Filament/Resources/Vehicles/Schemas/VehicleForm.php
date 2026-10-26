<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class VehicleForm
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
                    TextInput::make('vin')
                        ->required()
                        ->maxLength(32),
                    TextInput::make('year'),
                    TextInput::make('make')
                        ->maxLength(64),
                    TextInput::make('model')
                        ->maxLength(64),
                    TextInput::make('color')
                        ->maxLength(32),
                    Select::make('status')
                        ->options(VehicleStatus::class)
                        ->default(VehicleStatus::USED->value),
                    Select::make('type')
                        ->options(VehicleType::class)
                        ->default(VehicleType::CAR->value),
                ]),
        ];
    }
}
