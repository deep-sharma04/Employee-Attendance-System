<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager', 'team_lead', 'employee']);
    }

    public function view(User $user, Team $team): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'manager'])) {
            return true;
        }

        if ($role === 'team_lead') {
            return (int) $team->team_lead_id === (int) $user->id;
        }

        if ($role === 'employee') {
            return $team->teamMembers()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function update(User $user, Team $team): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function delete(User $user, Team $team): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }
}
