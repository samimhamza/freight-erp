<?php

namespace App\Filament\Resources\ImportAgents\Pages;

use App\Filament\Resources\ImportAgents\ImportAgentResource;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewImportAgent extends ViewRecord
{
    protected static string $resource = ImportAgentResource::class;

    protected $listeners = ['refreshViewImportAgent' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => ! $record->trashed()),
            DeleteAction::make()
                ->recordTitle(fn ($record) => 'Import Agent ('.$record->name.')'),
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
                Section::make('Import Agent Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Company Name'),
                        TextEntry::make('email')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('phone')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('fax')
                            ->visible(fn ($state) => $state),
                        TextEntry::make('address')
                            ->visible(fn ($state) => $state)
                            ->columnSpanFull(),
                        ...HasTimestampsAndUsers::timestampsWithUsersInfolist(),
                    ])
                    ->columns(2),
            ]);
    }
}
