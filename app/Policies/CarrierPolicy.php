<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Carrier;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CarrierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Carrier');
    }

    public function view(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('View:Carrier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Carrier');
    }

    public function update(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('Update:Carrier');
    }

    public function delete(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('Delete:Carrier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Carrier');
    }

    public function restore(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('Restore:Carrier');
    }

    public function forceDelete(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('ForceDelete:Carrier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Carrier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Carrier');
    }

    public function replicate(AuthUser $authUser, Carrier $carrier): bool
    {
        return $authUser->can('Replicate:Carrier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Carrier');
    }
}
