<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExportAgent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExportAgentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExportAgent');
    }

    public function view(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('View:ExportAgent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExportAgent');
    }

    public function update(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('Update:ExportAgent');
    }

    public function delete(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('Delete:ExportAgent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExportAgent');
    }

    public function restore(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('Restore:ExportAgent');
    }

    public function forceDelete(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('ForceDelete:ExportAgent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExportAgent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExportAgent');
    }

    public function replicate(AuthUser $authUser, ExportAgent $exportAgent): bool
    {
        return $authUser->can('Replicate:ExportAgent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExportAgent');
    }
}
