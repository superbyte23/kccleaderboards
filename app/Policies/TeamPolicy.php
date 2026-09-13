<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->can('view', $team->event);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->can('update', $team->event);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can('update', $team->event);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('update', $team->event);
    }

    public function restore(User $user, Team $team): bool
    {
        return $user->can('update', $team->event);
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $user->can('forceDelete', $team->event);
    }
}