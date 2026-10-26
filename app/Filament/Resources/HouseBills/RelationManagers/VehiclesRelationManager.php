<?php

namespace App\Filament\Resources\HouseBills\RelationManagers;

use App\Filament\Resources\Vehicles\Schemas\VehicleForm;
use App\Filament\Resources\Vehicles\Tables\VehiclesTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(VehicleForm::components());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(VehiclesTable::columns())
            ->filters(VehiclesTable::filters())
            ->headerActions([
                CreateAction::make()
                    ->label('New Vehicle')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                RestoreAction::make()->label(''),
                ForceDeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
