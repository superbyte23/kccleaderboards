<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function view(User $user, Result $result): bool
    {
        return $user->can('view', $result->competition->event);
    }

    public function create(User $user, Result $result): bool
    {
        return $user->can('update', $result->competition->event);
    }

    public function update(User $user, Result $result): bool
    {
        return $user->can('update', $result->competition->event);
    }

    public function delete(User $user, Result $result): bool
    {
        return $user->can('update', $result->competition->event);
    }

    public function restore(User $user, Result $result): bool
    {
        return $user->can('update', $result->competition->event);
    }

    public function forceDelete(User $user, Result $result): bool
    {
        return $user->can('forceDelete', $result->competition->event);
    }
}