<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager', 'team_lead', 'employee', 'client']);
    }

    public function view(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if ($role === 'super_admin') {
            return true;
        }

        if ($role === 'manager') {
            return (int) $project->manager_id === (int) $user->id
                || ($project->team && (int) $project->team->manager_id === (int) $user->id)
                || $project->projectMembers()->where('user_id', $user->id)->exists()
                || (int) $project->created_by === (int) $user->id
                || $user->isManager();
        }

        if ($role === 'team_lead') {
            return ($project->team && (int) $project->team->team_lead_id === (int) $user->id)
                || $project->projectMembers()->where('user_id', $user->id)->exists();
        }

        if ($role === 'employee') {
            return $project->projectMembers()->where('user_id', $user->id)->exists()
                || ($project->team && $project->team->teamMembers()->where('user_id', $user->id)->exists());
        }

        if ($role === 'client') {
            return (bool) ($project->client_id && $user->clientUser && (int) $user->clientUser->client_id === (int) $project->client_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function update(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function delete(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can view sensitive financial data (budget, labor costs, hourly rates).
     * Strictly restricted to Super Admin and Manager.
     */
    public function viewFinancials(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can upload documents to the project (Tasks T250, T254).
     * Manager, Super Admin, and assigned Team Lead can upload.
     */
    public function uploadDocument(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'manager'])) {
            return true;
        }

        if ($role === 'team_lead') {
            return ($project->team && (int) $project->team->team_lead_id === (int) $user->id)
                || $project->projectMembers()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage (toggle share, delete) project documents (Tasks T253, T254).
     * Strictly Super Admin and Manager.
     */
    public function manageDocuments(User $user, Project $project): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'manager']);
    }
}
