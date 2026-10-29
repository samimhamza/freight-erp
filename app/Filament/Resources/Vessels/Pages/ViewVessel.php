<?php

namespace App\Filament\Resources\Vessels\Pages;

use App\Filament\Resources\Vessels\VesselResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewVessel extends ViewRecord
{
    protected static string $resource = VesselResource::class;

    protected $listeners = ['refreshViewVessel' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Vessel ('.$record->name.')'),
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
                Section::make('Vessel Detials')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('voyage')
                            ->visible(fn ($state) => $state),
                        ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                    ])
                    ->columns(3),
            ]);
    }
}
