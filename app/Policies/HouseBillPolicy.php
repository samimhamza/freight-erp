<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HouseBill;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HouseBillPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HouseBill');
    }

    public function view(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('View:HouseBill');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HouseBill');
    }

    public function update(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('Update:HouseBill');
    }

    public function delete(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('Delete:HouseBill');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HouseBill');
    }

    public function restore(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('Restore:HouseBill');
    }

    public function forceDelete(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('ForceDelete:HouseBill');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HouseBill');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HouseBill');
    }

    public function replicate(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('Replicate:HouseBill');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HouseBill');
    }

    public function print(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('Print:HouseBill');
    }

    public function arrivalNotice(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('ArrivalNotice:HouseBill');
    }

    public function sendArrivalNotice(AuthUser $authUser, HouseBill $houseBill): bool
    {
        return $authUser->can('ArrivalNotice:HouseBill');
    }
}
