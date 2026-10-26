<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser, User $user): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        if ($user->hasRole('super_admin') && ! $authUser->hasRole('super_admin')) {
            return false;
        }

        return $authUser->hasRole('super_admin') || $authUser->can('Update:User');
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        // A user cannot delete their own account
        if ($authUser->id === $user->id) {
            return false;
        }

        // Only a super_admin can delete another super_admin
        if ($user->hasRole('super_admin')) {
            return $authUser->hasRole('super_admin');
        }

        return $authUser->hasRole('super_admin') || $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('DeleteAny:User');
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        // A user cannot force delete their own account
        if ($authUser->id === $user->id) {
            return false;
        }

        // Only a super_admin can force delete another super_admin
        if ($user->hasRole('super_admin')) {
            return $authUser->hasRole('super_admin');
        }

        return $authUser->hasRole('super_admin') || $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser, User $user): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->can('Reorder:User');
    }
}
