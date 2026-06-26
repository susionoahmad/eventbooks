<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id;
    }

    public function create(User $user): bool
    {
        // Only owners, finance managers, or admins can write master data
        return $user->isAdmin() || $user->isFinance();
    }

    public function update(User $user, Client $client): bool
    {
        if ($user->tenant_id !== $client->tenant_id) {
            return false;
        }
        return $user->isAdmin() || $user->isFinance();
    }

    public function delete(User $user, Client $client): bool
    {
        if ($user->tenant_id !== $client->tenant_id) {
            return false;
        }
        // Only Owner can delete master records
        return $user->isOwner();
    }
}
