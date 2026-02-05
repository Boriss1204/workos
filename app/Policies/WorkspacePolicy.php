<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_user_id === $user->id;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_user_id === $user->id;
    }
}
