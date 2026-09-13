<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function view(User $user, Competition $competition): bool
    {
        return $user->can('view', $competition->event);
    }

    public function create(User $user, Competition $competition): bool
    {
        return $user->can('update', $competition->event);
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->can('update', $competition->event);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->can('update', $competition->event);
    }

    public function restore(User $user, Competition $competition): bool
    {
        return $user->can('update', $competition->event);
    }

    public function forceDelete(User $user, Competition $competition): bool
    {
        return $user->can('forceDelete', $competition->event);
    }
}