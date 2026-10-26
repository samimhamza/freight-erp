<?php

namespace App\Filament\Resources\Carriers\Pages;

use App\Filament\Resources\Carriers\CarrierResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewCarrier extends ViewRecord
{
    protected static string $resource = CarrierResource::class;

    protected $listeners = ['refreshViewCarrier' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Carrier ('.$record->name.')'),
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
                Section::make('Carrier Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Carrier Name'),
                        TextEntry::make('code')
                            ->label('Code')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('account_number')
                            ->label('Payee Account Number / Bank Code')
                            ->placeholder('—'),
                        ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                    ])
                    ->columns(2),
            ]);
    }
}
