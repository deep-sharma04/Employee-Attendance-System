<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function view(User $user, Client $client): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'manager'])) {
            return true;
        }

        // Client portal user can only view their own associated client organization
        if ($role === 'client') {
            return (bool) $client->clientUsers()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function update(User $user, Client $client): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function delete(User $user, Client $client): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }
}
