<?php

namespace App\Http\Controllers\TeamLead;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use App\Services\Audit\AuditLoggerService;
use App\Services\Task\TaskDependencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamLeadTaskController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected TaskDependencyService $dependencyService
    ) {}

    /**
     * Display Team Lead tasks dashboard / Kanban for their squad.
     */
    public function index(Request $request): View
    {
        $teamIds = Team::where('team_lead_id', Auth::id())->pluck('id')->toArray();

        $query = Task::with(['project', 'assignee', 'checklists'])
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('team_id', $teamIds)
                  ->orWhereHas('project', fn ($pq) => $pq->whereIn('team_id', $teamIds));
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tasks = $query->latest('id')->paginate(15)->withQueryString();

        return view('team-lead.tasks.index', compact('tasks'));
    }

    /**
     * Show task details in team lead scope.
     */
    public function show(Task $task): View
    {
        $task->load([
            'project',
            'milestone',
            'subtasks.assignee',
            'assignee.employee',
            'blockingTasks',
            'checklists',
            'comments.user',
            'attachments.user',
            'histories.user',
        ]);

        $blockers = $this->dependencyService->getUnresolvedBlockers($task);
        $isBlocked = $blockers->isNotEmpty();

        return view('team-lead.tasks.show', compact('task', 'blockers', 'isBlocked'));
    }
}
