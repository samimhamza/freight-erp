<?php

namespace App\Filament\Resources\MasterBills\Tables;

use App\Filament\Resources\MasterBills\MasterBillResource;
use App\Models\MasterBill;
use App\Traits\HasTimestampsAndUsers;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Response;

class MasterBillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                Action::make('print')
                    ->label('')
                    ->icon('heroicon-o-printer')
                    ->url(fn (MasterBill $record): string => route('master-bills.print', $record))
                    ->openUrlInNewTab()
                    ->authorize('print'),
                Action::make('generateTxt')
                    ->label('')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($record) {
                        $content = MasterBill::getTxtContent($record);
                        $filename = "COSU{$record->ocean_bill_of_lading}.txt";

                        return Response::streamDownload(
                            function () use ($content) {
                                echo $content;
                            },
                            $filename,
                            ['Content-Type' => 'text/plain']
                        );
                    }),
                ViewAction::make()->label(''),
                EditAction::make()->label('')
                    ->visible(fn ($record) => ! $record->trashed()),
                DeleteAction::make()->label(''),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('activities')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->url(fn ($record) => MasterBillResource::getUrl('activities', ['record' => $record])),
            ])
            ->toolbarActions([
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('date')
                ->dateTime('Y-m-d h:i A')
                ->sortable(),
            TextColumn::make('console')
                ->sortable(),
            TextColumn::make('ocean_bill_of_lading')
                ->sortable(),
            TextColumn::make('vessel.name')
                ->sortable()
                ->searchable(),
            TextColumn::make('carrier.name')
                ->label('Carrier')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('origin.code')
                ->sortable(),
            TextColumn::make('portOfDischarge.code')
                ->sortable(),
            TextColumn::make('destination.code')
                ->sortable(),
            TextColumn::make('etd')
                ->label('ETD')
                ->date()
                ->sortable(),
            TextColumn::make('eta')
                ->label('ETA')
                ->date()
                ->sortable(),
            TextColumn::make('ata')
                ->label('ATA')
                ->date()
                ->sortable(),
            TextColumn::make('house_bills_count')
                ->counts('houseBills')
                ->label('Total House Bills'),
            TextColumn::make('total_containers')
                ->label('Total Containers'),
            TextColumn::make('total_vehicles')
                ->label('Total Vehicles'),
            TextColumn::make('total_packages')
                ->label('Total Packages'),
            TextColumn::make('total_weight')
                ->label('Total Weight'),
            TextColumn::make('total_volume')
                ->label('Total Volume'),
            ...HasTimestampsAndUsers::timestampsWithUsers(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('origin')
                ->relationship('origin', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('destination')
                ->relationship('destination', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('portOfDischarge')
                ->relationship('portOfDischarge', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('exportAgent')
                ->relationship('exportAgent', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('importAgent')
                ->relationship('importAgent', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('carrier')
                ->relationship('carrier', 'name')
                ->searchable()
                ->preload(),
            TrashedFilter::make()
                ->visible(fn () => auth()->user()->can('ForceDelete:MasterBill')),
            ...HasTimestampsAndUsers::logFilters(showDeleted: fn ($livewire) => HasTimestampsAndUsers::showDeleted($livewire)),
        ];
    }
}
