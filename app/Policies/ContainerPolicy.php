<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Container;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ContainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Container');
    }

    public function view(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('View:Container');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Container');
    }

    public function update(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('Update:Container');
    }

    public function delete(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('Delete:Container');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Container');
    }

    public function restore(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('Restore:Container');
    }

    public function forceDelete(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('ForceDelete:Container');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Container');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Container');
    }

    public function replicate(AuthUser $authUser, Container $container): bool
    {
        return $authUser->can('Replicate:Container');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Container');
    }
}
