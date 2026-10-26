<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (User $record): bool => $record->id === auth()->id() || ($record->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin'))),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->id === auth()->id() && $this->record->hasRole('super_admin')) {
            unset($data['roles']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->id === auth()->id() && ! $this->record->hasRole('super_admin')) {
            $this->record->assignRole('super_admin');
        }
    }
}
