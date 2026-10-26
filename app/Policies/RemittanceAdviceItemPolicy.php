<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RemittanceAdviceItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RemittanceAdviceItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RemittanceAdvice')
            || $authUser->can('ViewAny:RemittanceInvoice')
            || $authUser->can('ViewAny:PayableInvoice');
    }

    public function view(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('View:RemittanceAdvice')
            || $authUser->can('View:RemittanceInvoice')
            || $authUser->can('View:PayableInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RemittanceAdvice')
            || $authUser->can('Create:RemittanceInvoice')
            || $authUser->can('Create:PayableInvoice');
    }

    public function update(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('Update:RemittanceAdvice')
            || $authUser->can('Update:RemittanceInvoice')
            || $authUser->can('Update:PayableInvoice');
    }

    public function delete(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('Delete:RemittanceAdvice')
            || $authUser->can('Delete:RemittanceInvoice')
            || $authUser->can('Delete:PayableInvoice');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RemittanceAdvice')
            || $authUser->can('DeleteAny:RemittanceInvoice')
            || $authUser->can('DeleteAny:PayableInvoice');
    }

    public function restore(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('Restore:RemittanceAdvice')
            || $authUser->can('Restore:RemittanceInvoice')
            || $authUser->can('Restore:PayableInvoice');
    }

    public function forceDelete(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('ForceDelete:RemittanceAdvice')
            || $authUser->can('ForceDelete:RemittanceInvoice')
            || $authUser->can('ForceDelete:PayableInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RemittanceAdvice')
            || $authUser->can('ForceDeleteAny:RemittanceInvoice')
            || $authUser->can('ForceDeleteAny:PayableInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RemittanceAdvice')
            || $authUser->can('RestoreAny:RemittanceInvoice')
            || $authUser->can('RestoreAny:PayableInvoice');
    }

    public function replicate(AuthUser $authUser, RemittanceAdviceItem $remittanceAdviceItem): bool
    {
        return $authUser->can('Replicate:RemittanceAdvice')
            || $authUser->can('Replicate:RemittanceInvoice')
            || $authUser->can('Replicate:PayableInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RemittanceAdvice')
            || $authUser->can('Reorder:RemittanceInvoice')
            || $authUser->can('Reorder:PayableInvoice');
    }
}

