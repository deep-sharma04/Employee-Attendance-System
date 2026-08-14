<?php

namespace App\Services\AI\Tools;

use App\Models\Employee;
use App\Models\User;

class EmployeeMcpTools
{
    /**
     * T283: employee.search (Read-Only)
     * Strictly returns sanitized, non-sensitive project/team staff data.
     * Denies salary, bank details, tax numbers, payroll, attendance IPs, and leaves.
     */
    public function search(User $user, array $args): array
    {
        // Clients cannot access internal employee search
        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Client users are not permitted to search employees.');
        }

        $query = Employee::with(['user', 'teamMemberships.team']);

        // Scope per role
        if ($user->isSuperAdmin()) {
            // Global access
        } elseif ($user->isManager()) {
            // Scoped to teams or projects managed by manager
            $query->where(function ($q) use ($user) {
                $q->whereHas('teamMemberships.team', fn ($t) => $t->where('manager_id', $user->id))
                  ->orWhere('user_id', $user->id);
            });
        } elseif ($user->isTeamLead()) {
            // Scoped to team led by the team lead
            $query->whereHas('teamMemberships.team', fn ($t) => $t->where('team_lead_id', $user->id));
        } elseif ($user->isEmployee()) {
            // Scoped to the same teams as the employee or themselves
            $teamIds = $user->teamMemberships()->pluck('team_id')->toArray();
            $query->where(function ($q) use ($teamIds, $user) {
                if (!empty($teamIds)) {
                    $q->whereHas('teamMemberships', fn ($tm) => $tm->whereIn('team_id', $teamIds));
                }
                $q->orWhere('user_id', $user->id);
            });
        }

        if (!empty($args['search'])) {
            $search = (string) $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if (!empty($args['department'])) {
            $query->where('department', (string) $args['department']);
        }

        if (!empty($args['designation'])) {
            $query->where('designation', (string) $args['designation']);
        }

        if (!empty($args['team_id'])) {
            $query->whereHas('teamMemberships', fn ($tm) => $tm->where('team_id', (int) $args['team_id']));
        }

        $limit = min(50, max(1, (int) ($args['limit'] ?? 15)));
        $employees = $query->take($limit)->get();

        $sanitized = $employees->map(function (Employee $e) {
            $teams = $e->teamMemberships->map(fn ($tm) => [
                'team_id' => $tm->team_id,
                'team_name' => $tm->team?->name,
                'is_primary' => (bool) $tm->is_primary,
            ])->values()->all();

            return [
                'id' => $e->id,
                'user_id' => $e->user_id,
                'employee_code' => $e->employee_code,
                'name' => $e->first_name . ' ' . $e->last_name,
                'first_name' => $e->first_name,
                'last_name' => $e->last_name,
                'department' => $e->department,
                'designation' => $e->designation,
                'status' => $e->status?->value ?? (string) $e->status,
                'teams' => $teams,
            ];
        })->all();

        return [
            'employees' => $sanitized,
            'count' => count($sanitized),
        ];
    }
}
