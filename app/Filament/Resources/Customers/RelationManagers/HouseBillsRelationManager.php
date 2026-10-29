<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\HouseBills\Schemas\HouseBillForm;
use App\Filament\Resources\HouseBills\Tables\HouseBillsTable;
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

class HouseBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'houseBills';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(HouseBillForm::components());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'masterBill',
                'customer',
                'consignor',
                'notifyParty',
                'containers.container',
                'containers.vehicles',
            ]))
            ->recordTitleAttribute('id')
            ->columns(HouseBillsTable::columns())
            ->filters(HouseBillsTable::filters())
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn ($record) => ! $record->trashed()),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
