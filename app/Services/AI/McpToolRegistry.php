<?php

namespace App\Services\AI;

use App\DTOs\AI\McpRequestContext;
use App\Services\AI\Tools\ClientMcpTools;
use App\Services\AI\Tools\EmployeeMcpTools;
use App\Services\AI\Tools\IntelligenceMcpTools;
use App\Services\AI\Tools\ProjectMcpTools;
use App\Services\AI\Tools\TaskMcpTools;
use App\Services\AI\Tools\TimesheetMcpTools;
use App\Services\AI\Tools\WorkflowMcpTools;

class McpToolRegistry
{
    /**
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     category: string,
     *     type: string,
     *     requires_approval: bool,
     *     input_schema: array,
     *     handler: callable
     * }>
     */
    protected array $tools = [];

    public function __construct(
        protected McpIntegrationService $integrationService,
        protected ClientMcpTools $clientTools,
        protected ProjectMcpTools $projectTools,
        protected TaskMcpTools $taskTools,
        protected TimesheetMcpTools $timesheetTools,
        protected EmployeeMcpTools $employeeTools,
        protected IntelligenceMcpTools $intelligenceTools,
        protected WorkflowMcpTools $workflowTools
    ) {
        $this->registerAllTools();
    }

    /**
     * Register all Phase 31 MCP tools into the central registry.
     */
    public function registerAllTools(): void
    {
        // 1. Client Tools (T279)
        $this->registerTool([
            'name' => 'client.search',
            'required_permissions' => ['manage.clients'],
            'description' => 'Search clients by company name, code, or status within authorized scope.',
            'category' => 'client',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Search keyword for name, code, or email'],
                    'status' => ['type' => 'string', 'enum' => ['lead', 'active', 'inactive'], 'description' => 'Filter by client status'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'description' => 'Maximum results to return'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->clientTools->search($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'client.create',
            'required_permissions' => ['manage.clients'],
            'description' => 'Create a new client with contact and billing details.',
            'category' => 'client',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['company_name', 'status'],
                'properties' => [
                    'company_name' => ['type' => 'string', 'maxLength' => 150],
                    'company_code' => ['type' => 'string', 'maxLength' => 50],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'website' => ['type' => 'string'],
                    'address' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['lead', 'active', 'inactive']],
                    'currency' => ['type' => 'string', 'maxLength' => 10],
                    'billing_type' => ['type' => 'string'],
                    'notes' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->clientTools->create($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'client.update',
            'required_permissions' => ['manage.clients'],
            'description' => 'Update an existing client details.',
            'category' => 'client',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['client_id'],
                'properties' => [
                    'client_id' => ['type' => 'integer'],
                    'company_name' => ['type' => 'string', 'maxLength' => 150],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'website' => ['type' => 'string'],
                    'address' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['lead', 'active', 'inactive']],
                    'currency' => ['type' => 'string'],
                    'billing_type' => ['type' => 'string'],
                    'notes' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->clientTools->update($ctx->user, $ctx->arguments),
        ]);

        // 2. Project Tools (T280)
        $this->registerTool([
            'name' => 'project.search',
            'required_permissions' => ['manage.projects', 'assign.tasks', 'log.timesheets'],
            'description' => 'Search projects authorized for the invoking user.',
            'category' => 'project',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Search term for name or code'],
                    'status' => ['type' => 'string', 'enum' => ['planning', 'active', 'on_hold', 'completed', 'cancelled']],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'client_id' => ['type' => 'integer'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->projectTools->search($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'project.create',
            'required_permissions' => ['manage.projects'],
            'description' => 'Create a new project under authorized client and team scope.',
            'category' => 'project',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['name', 'code', 'status', 'priority', 'start_date', 'deadline'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 150],
                    'code' => ['type' => 'string', 'maxLength' => 50],
                    'client_id' => ['type' => 'integer'],
                    'team_id' => ['type' => 'integer'],
                    'manager_id' => ['type' => 'integer'],
                    'budget' => ['type' => 'number', 'minimum' => 0],
                    'status' => ['type' => 'string', 'enum' => ['planning', 'active', 'on_hold', 'completed', 'cancelled']],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'health' => ['type' => 'string', 'enum' => ['good', 'warning', 'critical']],
                    'start_date' => ['type' => 'string', 'format' => 'date'],
                    'deadline' => ['type' => 'string', 'format' => 'date'],
                    'description' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->projectTools->create($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'project.update',
            'required_permissions' => ['manage.projects'],
            'description' => 'Update an existing project.',
            'category' => 'project',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['project_id'],
                'properties' => [
                    'project_id' => ['type' => 'integer'],
                    'name' => ['type' => 'string', 'maxLength' => 150],
                    'client_id' => ['type' => 'integer'],
                    'team_id' => ['type' => 'integer'],
                    'manager_id' => ['type' => 'integer'],
                    'budget' => ['type' => 'number', 'minimum' => 0],
                    'status' => ['type' => 'string', 'enum' => ['planning', 'active', 'on_hold', 'completed', 'cancelled']],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'health' => ['type' => 'string', 'enum' => ['good', 'warning', 'critical']],
                    'start_date' => ['type' => 'string', 'format' => 'date'],
                    'deadline' => ['type' => 'string', 'format' => 'date'],
                    'description' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->projectTools->update($ctx->user, $ctx->arguments),
        ]);

        // 3. Task Tools (T281)
        $this->registerTool([
            'name' => 'task.create',
            'required_permissions' => ['assign.tasks'],
            'description' => 'Create a new task within a project.',
            'category' => 'task',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['project_id', 'title', 'task_code', 'priority', 'status'],
                'properties' => [
                    'project_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string', 'maxLength' => 200],
                    'task_code' => ['type' => 'string', 'maxLength' => 50],
                    'milestone_id' => ['type' => 'integer'],
                    'parent_id' => ['type' => 'integer'],
                    'assigned_to' => ['type' => 'integer'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'status' => ['type' => 'string', 'enum' => ['todo', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled']],
                    'estimated_hours' => ['type' => 'number', 'minimum' => 0],
                    'due_date' => ['type' => 'string', 'format' => 'date'],
                    'description' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->taskTools->create($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'task.update',
            'required_permissions' => ['assign.tasks'],
            'description' => 'Update task properties, status, or deadline.',
            'category' => 'task',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['task_id'],
                'properties' => [
                    'task_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string', 'maxLength' => 200],
                    'milestone_id' => ['type' => 'integer'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'status' => ['type' => 'string', 'enum' => ['todo', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled']],
                    'estimated_hours' => ['type' => 'number', 'minimum' => 0],
                    'due_date' => ['type' => 'string', 'format' => 'date'],
                    'description' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->taskTools->update($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'task.assign',
            'required_permissions' => ['assign.tasks'],
            'description' => 'Assign a task to an active, eligible team member.',
            'category' => 'task',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['task_id', 'assigned_to'],
                'properties' => [
                    'task_id' => ['type' => 'integer'],
                    'assigned_to' => ['type' => 'integer', 'description' => 'User ID of the assignee'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->taskTools->assign($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'task.complete',
            'required_permissions' => ['assign.tasks', 'log.timesheets'],
            'description' => 'Mark a task as completed (enforcing blocker checks).',
            'category' => 'task',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['task_id'],
                'properties' => [
                    'task_id' => ['type' => 'integer'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->taskTools->complete($ctx->user, $ctx->arguments),
        ]);

        // 4. Timesheet Tools (T282)
        $this->registerTool([
            'name' => 'timesheet.search',
            'required_permissions' => ['approve.timesheets', 'log.timesheets'],
            'description' => 'Search timesheets within authorized user and project scope.',
            'category' => 'timesheet',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'project_id' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'submitted', 'approved', 'rejected', 'returned']],
                    'start_date' => ['type' => 'string', 'format' => 'date'],
                    'end_date' => ['type' => 'string', 'format' => 'date'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->timesheetTools->search($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'timesheet.create',
            'required_permissions' => ['log.timesheets'],
            'description' => 'Create a draft weekly or daily timesheet with logged project hours.',
            'category' => 'timesheet',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['start_date', 'end_date', 'period_type', 'entries'],
                'properties' => [
                    'employee_id' => ['type' => 'integer'],
                    'start_date' => ['type' => 'string', 'format' => 'date'],
                    'end_date' => ['type' => 'string', 'format' => 'date'],
                    'period_type' => ['type' => 'string', 'enum' => ['weekly', 'daily']],
                    'entries' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['project_id', 'entry_date', 'hours'],
                            'properties' => [
                                'project_id' => ['type' => 'integer'],
                                'task_id' => ['type' => 'integer'],
                                'entry_date' => ['type' => 'string', 'format' => 'date'],
                                'hours' => ['type' => 'number', 'minimum' => 0.25, 'maximum' => 24],
                                'is_billable' => ['type' => 'boolean'],
                                'description' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->timesheetTools->create($ctx->user, $ctx->arguments),
        ]);

        // 5. Restricted Employee Search (T283) - Strictly READ ONLY
        $this->registerTool([
            'name' => 'employee.search',
            'required_permissions' => ['manage.employees', 'manage.teams', 'assign.tasks', 'log.timesheets'],
            'description' => 'Search project and team staff (strictly excludes salary, bank, tax, payroll, and IP allowlist data).',
            'category' => 'employee',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Search by name, code, department, or designation'],
                    'department' => ['type' => 'string'],
                    'designation' => ['type' => 'string'],
                    'team_id' => ['type' => 'integer'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->employeeTools->search($ctx->user, $ctx->arguments),
        ]);

        // 6. Project Intelligence Tools (Phase 32: T285-T289)
        $this->registerTool([
            'name' => 'project.intelligence_search',
            'required_permissions' => ['manage.projects', 'assign.tasks', 'log.timesheets', 'view.project_reports'],
            'description' => 'Execute natural-language project queries (overdue tasks, upcoming deadlines, project status, workloads) with authorized scoping.',
            'category' => 'intelligence',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Natural-language question or search terms'],
                    'intent' => ['type' => 'string', 'enum' => ['overdue_tasks', 'upcoming_tasks', 'projects_with_overdue', 'incomplete_projects', 'project_workload', 'project_status', 'project_tasks']],
                    'project_id' => ['type' => 'integer'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->intelligenceTools->search($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'project.explain_health',
            'required_permissions' => ['manage.projects', 'view.project_reports'],
            'description' => 'Explain deterministic project health calculation with grounded milestone, schedule variance, and overdue task evidence.',
            'category' => 'intelligence',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['project_id'],
                'properties' => [
                    'project_id' => ['type' => 'integer'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->intelligenceTools->explainHealth($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'task.recommend_allocation',
            'required_permissions' => ['manage.projects', 'assign.tasks'],
            'description' => 'Recommend candidate team members for task allocation based on skills, availability, and active workload (Read-only; does not mutate assignments).',
            'category' => 'intelligence',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'project_id' => ['type' => 'integer'],
                    'task_id' => ['type' => 'integer'],
                    'required_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'estimated_hours' => ['type' => 'number', 'minimum' => 0],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->intelligenceTools->recommendAllocation($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'project.management_report',
            'required_permissions' => ['manage.projects', 'view.project_reports'],
            'description' => 'Generate structured management intelligence summaries for productivity, team workload, and authorized budget utilization.',
            'category' => 'intelligence',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['report_type'],
                'properties' => [
                    'report_type' => ['type' => 'string', 'enum' => ['productivity', 'workload', 'budget_utilization']],
                    'project_id' => ['type' => 'integer'],
                    'team_id' => ['type' => 'integer'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->intelligenceTools->managementReport($ctx->user, $ctx->arguments),
        ]);

        // 7. Workflow Execution & Approval Tools (Phase 33: T290-T295)
        $this->registerTool([
            'name' => 'task.bulk_reassign',
            'required_permissions' => ['assign.tasks'],
            'description' => 'Reassign all active tasks from one team member to another (Sensitive/Destructive action; requires approval if requested by Team Lead).',
            'category' => 'workflow',
            'type' => 'destructive',
            'requires_approval' => true,
            'input_schema' => [
                'type' => 'object',
                'required' => ['from_user_id', 'to_user_id'],
                'properties' => [
                    'from_user_id' => ['type' => 'integer'],
                    'to_user_id' => ['type' => 'integer'],
                    'project_id' => ['type' => 'integer'],
                    'requires_approval' => ['type' => 'boolean'],
                    'idempotency_key' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->workflowTools->bulkReassign($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'ai.action.pending_list',
            'required_permissions' => ['manage.projects', 'manage.teams'],
            'description' => 'List all pending AI workflow action proposals awaiting server-side approval within authorized scope.',
            'category' => 'workflow',
            'type' => 'read',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'properties' => [],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->workflowTools->pendingList($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'ai.action.approve',
            'required_permissions' => ['manage.projects', 'manage.teams'],
            'description' => 'Approve a pending AI workflow action proposal and trigger its atomic execution (Super Admin & Manager only).',
            'category' => 'workflow',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['action_log_id'],
                'properties' => [
                    'action_log_id' => ['type' => 'integer'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->workflowTools->approve($ctx->user, $ctx->arguments),
        ]);

        $this->registerTool([
            'name' => 'ai.action.reject',
            'required_permissions' => ['manage.projects', 'manage.teams'],
            'description' => 'Reject a pending AI workflow action proposal with a mandatory rejection rationale.',
            'category' => 'workflow',
            'type' => 'mutation',
            'requires_approval' => false,
            'input_schema' => [
                'type' => 'object',
                'required' => ['action_log_id', 'reason'],
                'properties' => [
                    'action_log_id' => ['type' => 'integer'],
                    'reason' => ['type' => 'string'],
                ],
            ],
            'handler' => fn (McpRequestContext $ctx) => $this->workflowTools->reject($ctx->user, $ctx->arguments),
        ]);
    }

    /**
     * Register a tool into the internal registry and McpIntegrationService.
     */
    public function registerTool(array $toolDefinition): void
    {
        $name = $toolDefinition['name'];
        $this->tools[$name] = $toolDefinition;

        // Connect handler to McpIntegrationService
        $this->integrationService->registerToolHandler($name, $toolDefinition['handler'], $toolDefinition['required_permissions'] ?? []);
    }

    /**
     * Get all registered tools.
     */
    public function getAllTools(): array
    {
        return $this->tools;
    }

    /**
     * Get tool definition by name.
     */
    public function getTool(string $name): ?array
    {
        return $this->tools[$name] ?? null;
    }
}
