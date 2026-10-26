<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles as ShieldListRoles;
use Filament\Actions\CreateAction;

class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Role')
                ->icon('heroicon-o-plus'),
        ];
    }
}
