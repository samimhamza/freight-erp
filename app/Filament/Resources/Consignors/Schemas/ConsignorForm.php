<?php

namespace App\Filament\Resources\Consignors\Schemas;

use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ConsignorForm
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
                        ->maxLength(128),
                    TextInput::make('email')
                        ->unique(table: 'consignors', ignoreRecord: true)->live(onBlur: true)
                        ->email()
                        ->maxLength(64),
                    TextInput::make('phone')
                        ->unique(table: 'consignors', ignoreRecord: true)->live(onBlur: true)
                        ->tel(),
                    TextInput::make('fax')
                        ->tel(),
                    Textarea::make('address')
                        ->columnSpanFull(),
                    Select::make('country_id')
                        ->label('Country')
                        ->relationship('country', 'name')
                        ->createOptionForm(CountryForm::components())
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Country $record): string => "{$record->name} ({$record->code})"),
                ]),
        ];
    }
}
