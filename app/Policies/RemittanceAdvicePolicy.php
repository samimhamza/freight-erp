<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RemittanceAdvice;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RemittanceAdvicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RemittanceAdvice')
            || $authUser->can('ViewAny:RemittanceInvoice')
            || $authUser->can('ViewAny:PayableInvoice');
    }

    public function view(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
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

    public function update(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
    {
        return $authUser->can('Update:RemittanceAdvice')
            || $authUser->can('Update:RemittanceInvoice')
            || $authUser->can('Update:PayableInvoice');
    }

    public function delete(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
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

    public function restore(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
    {
        return $authUser->can('Restore:RemittanceAdvice')
            || $authUser->can('Restore:RemittanceInvoice')
            || $authUser->can('Restore:PayableInvoice');
    }

    public function forceDelete(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
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

    public function replicate(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
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

    public function print(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
    {
        return $authUser->can('Print:RemittanceAdvice')
            || $authUser->can('Print:RemittanceInvoice')
            || $authUser->can('Print:PayableInvoice');
    }

    public function send(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
    {
        return $authUser->can('Send:RemittanceAdvice')
            || $authUser->can('Send:RemittanceInvoice')
            || $authUser->can('Send:PayableInvoice')
            || $authUser->can('Update:RemittanceAdvice')
            || $authUser->can('Update:RemittanceInvoice')
            || $authUser->can('Update:PayableInvoice');
    }

    public function cancel(AuthUser $authUser, RemittanceAdvice $remittanceAdvice): bool
    {
        return $authUser->can('Update:RemittanceAdvice')
            || $authUser->can('Update:RemittanceInvoice')
            || $authUser->can('Update:PayableInvoice');
    }
}
