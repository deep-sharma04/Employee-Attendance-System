<?php

namespace App\Services\AI;

use App\Services\AI\Tools\AiActionApproveTool;
use App\Services\AI\Tools\AiActionPendingListTool;
use App\Services\AI\Tools\AiActionRejectTool;
use App\Services\AI\Tools\ClientCreateTool;
use App\Services\AI\Tools\ClientSearchTool;
use App\Services\AI\Tools\ClientUpdateTool;
use App\Services\AI\Tools\EmployeeSearchTool;
use App\Services\AI\Tools\ProjectCreateTool;
use App\Services\AI\Tools\ProjectExplainHealthTool;
use App\Services\AI\Tools\ProjectIntelligenceSearchTool;
use App\Services\AI\Tools\ProjectManagementReportTool;
use App\Services\AI\Tools\ProjectSearchTool;
use App\Services\AI\Tools\ProjectUpdateTool;
use App\Services\AI\Tools\TaskAssignTool;
use App\Services\AI\Tools\TaskBulkReassignTool;
use App\Services\AI\Tools\TaskCompleteTool;
use App\Services\AI\Tools\TaskCreateTool;
use App\Services\AI\Tools\TaskRecommendAllocationTool;
use App\Services\AI\Tools\TaskUpdateTool;
use App\Services\AI\Tools\TimesheetCreateTool;
use App\Services\AI\Tools\TimesheetSearchTool;
use Laravel\Mcp\Server;

class HrmMcpServer extends Server
{
    protected string $name = 'HRM Internal MCP Server';
    protected string $version = '1.0.0';
    protected string $instructions = 'Internal Model Context Protocol server for Employee Attendance & HR Management system. Provides controlled access to project, client, task, timesheet, and employee search tools under strict RBAC and project/team/client scoping.';

    public int $defaultPaginationLength = 50;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ClientSearchTool::class,
        ClientCreateTool::class,
        ClientUpdateTool::class,
        ProjectSearchTool::class,
        ProjectCreateTool::class,
        ProjectUpdateTool::class,
        TaskCreateTool::class,
        TaskUpdateTool::class,
        TaskAssignTool::class,
        TaskCompleteTool::class,
        TimesheetSearchTool::class,
        TimesheetCreateTool::class,
        EmployeeSearchTool::class,
        ProjectIntelligenceSearchTool::class,
        ProjectExplainHealthTool::class,
        TaskRecommendAllocationTool::class,
        ProjectManagementReportTool::class,
        TaskBulkReassignTool::class,
        AiActionPendingListTool::class,
        AiActionApproveTool::class,
        AiActionRejectTool::class,
    ];
}
