<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportAgent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ImportAgentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportAgent');
    }

    public function view(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('View:ImportAgent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportAgent');
    }

    public function update(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('Update:ImportAgent');
    }

    public function delete(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('Delete:ImportAgent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ImportAgent');
    }

    public function restore(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('Restore:ImportAgent');
    }

    public function forceDelete(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('ForceDelete:ImportAgent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportAgent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportAgent');
    }

    public function replicate(AuthUser $authUser, ImportAgent $importAgent): bool
    {
        return $authUser->can('Replicate:ImportAgent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportAgent');
    }
}
