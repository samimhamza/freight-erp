<?php

namespace App\Filament\Resources\Vessels\RelationManagers;

use App\Filament\Resources\MasterBills\MasterBillResource;
use App\Filament\Resources\MasterBills\Pages\ViewMasterBill;
use App\Filament\Resources\MasterBills\Tables\MasterBillsTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'masterBills';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'vessel',
                'carrier',
                'origin',
                'portOfDischarge',
                'destination',
                'houseBills.containers.container',
                'houseBills.containers.vehicles',
            ]))
            ->recordTitleAttribute('id')
            ->columns(MasterBillsTable::columns())
            ->filters(MasterBillsTable::filters())
            ->headerActions([
                CreateAction::make()
                    ->label('New Master Bill')
                    ->icon('heroicon-o-plus')
                    ->steps(MasterBillResource::getSteps(showVessel: false))
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewVessel');
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('')
                    ->recordTitle(fn ($record) => 'Master Bill ('.$record->ocean_bill_of_lading.')')
                    ->schema(ViewMasterBill::schema()),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->recordTitle(fn ($record) => 'Master Bill ('.$record->ocean_bill_of_lading.')')
                    ->steps(MasterBillResource::getSteps())
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewVessel');
                    }),
                DeleteAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->recordTitle(fn ($record) => 'Master Bill ('.$record->ocean_bill_of_lading.')')
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('refreshViewVessel');
                    }),
                RestoreAction::make()->label(''),
                ForceDeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
