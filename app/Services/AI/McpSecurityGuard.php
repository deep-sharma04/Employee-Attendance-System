<?php

namespace App\Services\AI;

use App\DTOs\AI\McpRequestContext;
use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class McpSecurityGuard
{
    /**
     * Prohibited sensitive HR / Payroll / Security keywords and tool targets.
     */
    protected const RESTRICTED_HR_TERMS = [
        'salary',
        'monthly_salary',
        'daily_salary',
        'basic_salary',
        'bank_account',
        'bank_account_number',
        'bank_name',
        'ifsc',
        'ifsc_code',
        'payroll',
        'payslip',
        'attendance_ip',
        'office_ip_allowlist',
        'ip_allowlist',
        'hr_mutation',
        'leave_mutation',
    ];

    /**
     * T271: Validate that requested tool and arguments do not access or expose restricted HR data.
     */
    public function checkHrDataIsolation(McpRequestContext $context): bool
    {
        $tool = strtolower($context->toolName);

        // Explicit tool name prohibitions
        foreach (self::RESTRICTED_HR_TERMS as $term) {
            if (str_contains($tool, $term)) {
                return false;
            }
        }

        // Check argument keys and string values for prohibited terms
        return $this->scanArrayForRestrictedTerms($context->arguments);
    }

    protected function scanArrayForRestrictedTerms(array $data): bool
    {
        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);
            foreach (self::RESTRICTED_HR_TERMS as $term) {
                if (str_contains($keyLower, $term)) {
                    return false;
                }
            }

            if (is_array($value)) {
                if (!$this->scanArrayForRestrictedTerms($value)) {
                    return false;
                }
            } elseif (is_string($value)) {
                $valLower = strtolower($value);
                foreach (self::RESTRICTED_HR_TERMS as $term) {
                    if (str_contains($valLower, $term)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * T270: Enforce strict user RBAC, project, team, and client scope.
     * Fails closed (returns false if scope is invalid or unauthorized).
     */
    public function validateScope(McpRequestContext $context): bool
    {
        $user = $context->user;

        // 1. Inactive users are strictly denied
        if (!$user->is_active) {
            return false;
        }

        // 2. Super Admin has global scope across all project management domains
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 3. Client Users: Strictly isolated to their own Client ID
        if ($user->isClient()) {
            $clientUser = $user->clientUser;
            if (!$clientUser || !$clientUser->client_id) {
                return false;
            }

            // If context specifies client_id, it must match client's own client_id
            if ($context->clientId && $context->clientId !== $clientUser->client_id) {
                return false;
            }

            // If context specifies project_id, project must belong to client
            if ($context->projectId) {
                $project = Project::find($context->projectId);
                if (!$project || $project->client_id !== $clientUser->client_id) {
                    return false;
                }
            }

            // Clients cannot execute internal management mutations
            if (str_contains($context->toolName, 'create') || str_contains($context->toolName, 'update') || str_contains($context->toolName, 'delete') || str_contains($context->toolName, 'assign')) {
                return false;
            }

            return true;
        }

        // 4. Project Scope Verification (Managers, Team Leads, Employees)
        if ($context->projectId) {
            $project = Project::find($context->projectId);
            if (!$project) {
                return false; // Project does not exist -> fail closed
            }

            if ($user->isManager()) {
                // Manager must be the project's manager or team manager
                if ($project->manager_id !== $user->id && (!$project->team || $project->team->manager_id !== $user->id)) {
                    return false;
                }
            } elseif ($user->isTeamLead()) {
                // Team Lead must lead the project's assigned team
                if (!$project->team || $project->team->team_lead_id !== $user->id) {
                    return false;
                }
            } elseif ($user->isEmployee()) {
                // Employee must be a member of the project team or assigned to tasks
                $isTeamMember = $project->team && $project->team->members()->where('users.id', $user->id)->exists();
                $hasAssignedTask = $project->tasks()->where('assigned_to', $user->id)->exists();

                if (!$isTeamMember && !$hasAssignedTask) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // 5. Team Scope Verification
        if ($context->teamId) {
            $team = Team::find($context->teamId);
            if (!$team) {
                return false;
            }

            if ($user->isManager() && $team->manager_id !== $user->id) {
                return false;
            }
            if ($user->isTeamLead() && $team->team_lead_id !== $user->id) {
                return false;
            }
            if ($user->isEmployee() && !$team->members()->where('users.id', $user->id)->exists()) {
                return false;
            }
        }

        // 6. Role-level tool restrictions
        if ($user->isEmployee()) {
            $restrictedForEmployees = [
                'project.create',
                'project.update',
                'task.bulk_reassign',
                'client.create',
                'client.update',
                'ai.action.approve',
                'ai.action.reject',
            ];
            if (in_array($context->toolName, $restrictedForEmployees, true)) {
                return false;
            }
        }

        if ($user->isTeamLead()) {
            $restrictedForTeamLeads = [
                'ai.action.approve',
                'ai.action.reject',
            ];
            if (in_array($context->toolName, $restrictedForTeamLeads, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * T272: Check if a user has authority to approve a specific action log.
     */
    public function canApproveAction(User $user, Project|int|null $project = null): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            if ($project === null) {
                return true;
            }
            $proj = $project instanceof Project ? $project : Project::find($project);
            return $proj && ($proj->manager_id === $user->id || ($proj->team && $proj->team->manager_id === $user->id));
        }

        // Team Leads and Employees CANNOT approve sensitive AI/MCP actions
        return false;
    }
}
