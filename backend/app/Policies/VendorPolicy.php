<?php

namespace App\Policies;

use App\Models\Vendor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->tenant_id === $vendor->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFinance();
    }

    public function update(User $user, Vendor $vendor): bool
    {
        if ($user->tenant_id !== $vendor->tenant_id) {
            return false;
        }
        return $user->isAdmin() || $user->isFinance();
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        if ($user->tenant_id !== $vendor->tenant_id) {
            return false;
        }
        return $user->isOwner();
    }
}
