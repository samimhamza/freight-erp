<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MasterBill;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MasterBillPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MasterBill');
    }

    public function view(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('View:MasterBill');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MasterBill');
    }

    public function update(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('Update:MasterBill');
    }

    public function delete(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('Delete:MasterBill');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MasterBill');
    }

    public function restore(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('Restore:MasterBill');
    }

    public function forceDelete(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('ForceDelete:MasterBill');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MasterBill');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MasterBill');
    }

    public function replicate(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('Replicate:MasterBill');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MasterBill');
    }

    public function print(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('Print:MasterBill');
    }

    public function sendArrivalNotices(AuthUser $authUser, MasterBill $masterBill): bool
    {
        return $authUser->can('ArrivalNotice:HouseBill');
    }
}
