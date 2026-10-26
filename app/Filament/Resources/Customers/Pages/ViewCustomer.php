<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Customer ('.$record->name.')'),
        ];
    }

    protected function resolveRecord(int|string $key): Model
    {
        $model = static::getResource()::getModel();

        return $model::withTrashed()->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Customer Detials')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('phone')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('fax')
                            ->visible(fn ($state) => $state),
                        ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                        TextEntry::make('address')
                            ->columnSpanFull()
                            ->visible(fn ($state) => $state),
                        TextEntry::make('country.name')
                            ->formatStateUsing(fn ($record) => "{$record->country->name} ({$record->country->code})"),
                    ])
                    ->columns(3),
            ]);
    }
}
