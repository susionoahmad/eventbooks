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
        // Owner, Admin, Finance, and Staff can create new events
        return $user->isStaff();
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Everyone including staff can update event details
        return $user->isStaff();
    }

    public function updateStatus(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Everyone including staff can change event status
        return $user->isStaff();
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
