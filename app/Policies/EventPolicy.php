<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Any authenticated user may list events (query is scoped to owned events).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Users may view events they own; admins may view anything.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    /**
     * Any authenticated user may create an event (they become the owner).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Users may update events they own; admins may update anything.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    /**
     * Users may delete events they own; admins may delete anything.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function forceDelete(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }
}