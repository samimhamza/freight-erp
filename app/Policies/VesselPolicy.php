<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Vessel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VesselPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vessel');
    }

    public function view(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('View:Vessel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vessel');
    }

    public function update(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('Update:Vessel');
    }

    public function delete(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('Delete:Vessel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Vessel');
    }

    public function restore(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('Restore:Vessel');
    }

    public function forceDelete(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('ForceDelete:Vessel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vessel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vessel');
    }

    public function replicate(AuthUser $authUser, Vessel $vessel): bool
    {
        return $authUser->can('Replicate:Vessel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vessel');
    }
}
