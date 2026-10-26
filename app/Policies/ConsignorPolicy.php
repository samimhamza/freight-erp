<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Consignor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConsignorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Consignor');
    }

    public function view(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('View:Consignor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Consignor');
    }

    public function update(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('Update:Consignor');
    }

    public function delete(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('Delete:Consignor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Consignor');
    }

    public function restore(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('Restore:Consignor');
    }

    public function forceDelete(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('ForceDelete:Consignor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Consignor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Consignor');
    }

    public function replicate(AuthUser $authUser, Consignor $consignor): bool
    {
        return $authUser->can('Replicate:Consignor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Consignor');
    }
}
