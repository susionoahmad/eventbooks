<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // All team members can view events
        return $user->isStaff();
    }

    public function view(User $user, Event $event): bool
    {
        // Must belong to the same tenant
        return $user->tenant_id === $event->tenant_id;
    }

    public function create(User $user): bool
    {
        // Only owners and admins can create new events
        return $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Staff can update events (e.g. details, adding items), but status modifications are protected
        return $user->isStaff();
    }

    public function updateStatus(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Only owners, finance managers, or admins can change event status (Draft -> DP -> Berjalan)
        return $user->isAdmin() || $user->isFinance();
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Only Owner can delete an event
        return $user->isOwner();
    }
}
