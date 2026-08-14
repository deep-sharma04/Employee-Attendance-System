<?php

namespace App\Services\AI\Tools;

use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectMcpTools
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * T280: project.search
     */
    public function search(User $user, array $args): array
    {
        if (!Gate::forUser($user)->allows('viewAny', Project::class)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to view projects.');
        }

        $query = Project::with(['client', 'team']);

        // Scope projects per user role & permissions
        if ($user->isClient()) {
            $clientId = $user->clientUser?->client_id;
            if (!$clientId) {
                return ['projects' => [], 'count' => 0];
            }
            $query->where('client_id', $clientId);
        } elseif ($user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('team', fn ($t) => $t->where('manager_id', $user->id));
            });
        } elseif ($user->isTeamLead()) {
            $query->whereHas('team', fn ($t) => $t->where('team_lead_id', $user->id));
        } elseif ($user->isEmployee()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id))
                  ->orWhereHas('team.teamMembers', fn ($tm) => $tm->where('user_id', $user->id));
            });
        }

        if (!empty($args['search'])) {
            $search = (string) $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($args['status'])) {
            $query->where('status', (string) $args['status']);
        }

        if (!empty($args['priority'])) {
            $query->where('priority', (string) $args['priority']);
        }

        if (!empty($args['client_id'])) {
            $query->where('client_id', (int) $args['client_id']);
        }

        $limit = min(50, max(1, (int) ($args['limit'] ?? 15)));
        $projects = $query->latest('id')->take($limit)->get();

        $sanitized = $projects->map(function (Project $p) use ($user) {
            $canViewFinancials = Gate::forUser($user)->allows('viewFinancials', $p);
            $item = [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'client_id' => $p->client_id,
                'client_name' => $p->client?->company_name,
                'team_id' => $p->team_id,
                'team_name' => $p->team?->name,
                'status' => $p->status->value,
                'priority' => $p->priority->value,
                'health' => $p->health->value,
                'start_date' => $p->start_date?->toDateString(),
                'deadline' => $p->deadline?->toDateString(),
                'description' => $p->description,
            ];

            if ($canViewFinancials) {
                $item['budget'] = (float) $p->budget;
            }

            return $item;
        })->all();

        return [
            'projects' => $sanitized,
            'count' => count($sanitized),
        ];
    }

    /**
     * T280: project.create
     */
    public function create(User $user, array $args): array
    {
        if (!Gate::forUser($user)->allows('create', Project::class)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to create projects.');
        }

        $validator = validator($args, [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'health' => ['nullable', Rule::enum(ProjectHealth::class)],
            'start_date' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $data['created_by'] = $user->id;
        $data['manager_id'] = $data['manager_id'] ?? ($user->isManager() ? $user->id : null);
        $data['health'] = $data['health'] ?? ProjectHealth::GOOD->value;

        $project = Project::create($data);

        $this->auditLogger->logProject(
            action: 'project.created',
            projectId: $project->id,
            afterValues: $project->toArray(),
            description: "Project '{$project->name}' ({$project->code}) created via MCP by {$user->name}."
        );

        return [
            'project_id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'status' => $project->status->value,
            'message' => "Project '{$project->name}' created successfully.",
        ];
    }

    /**
     * T280: project.update
     */
    public function update(User $user, array $args): array
    {
        $projectId = (int) ($args['project_id'] ?? 0);
        $project = Project::findOrFail($projectId);

        if (!Gate::forUser($user)->allows('update', $project)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to update this project.');
        }

        $validator = validator($args, [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::enum(ProjectStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(ProjectPriority::class)],
            'health' => ['nullable', Rule::enum(ProjectHealth::class)],
            'start_date' => ['sometimes', 'required', 'date'],
            'deadline' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $before = $project->toArray();
        $project->update($validator->validated());

        $this->auditLogger->logProject(
            action: 'project.updated',
            projectId: $project->id,
            beforeValues: $before,
            afterValues: $project->toArray(),
            description: "Project '{$project->name}' updated via MCP by {$user->name}."
        );

        return [
            'project_id' => $project->id,
            'name' => $project->name,
            'status' => $project->status->value,
            'message' => "Project '{$project->name}' updated successfully.",
        ];
    }
}
