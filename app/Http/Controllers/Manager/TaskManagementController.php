<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\TaskHistory;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Notification\NotificationService;
use App\Services\Task\OverdueTaskDetectionService;
use App\Services\Task\RecurringTaskService;
use App\Services\Task\TaskDependencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected TaskDependencyService $dependencyService,
        protected RecurringTaskService $recurringService,
        protected OverdueTaskDetectionService $overdueService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a listing of tasks (List View).
     */
    public function index(Request $request): View
    {
        $query = Task::with(['project', 'milestone', 'assignee', 'parent']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%");
            });
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($milestoneId = $request->input('milestone_id')) {
            $query->where('milestone_id', $milestoneId);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($request->boolean('overdue')) {
            $query->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now()->toDateString());
        }

        $tasks = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => Task::count(),
            'todo' => Task::where('status', TaskStatus::TODO->value)->count(),
            'in_progress' => Task::where('status', TaskStatus::IN_PROGRESS->value)->count(),
            'in_review' => Task::where('status', TaskStatus::IN_REVIEW->value)->count(),
            'blocked' => Task::where('status', TaskStatus::BLOCKED->value)->count(),
            'done' => Task::where('status', TaskStatus::DONE->value)->count(),
            'overdue' => $this->overdueService->getOverdueTasks()->count(),
        ];

        $projects = Project::whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::CANCELLED->value])
            ->orderBy('name')
            ->get();
        $assignees = User::with(['employee', 'teamMemberships.team'])
            ->where('is_active', true)
            ->whereNotIn('role', [UserRole::CLIENT])
            ->orderBy('name')
            ->get();

        return view('manager.tasks.index', compact('tasks', 'stats', 'projects', 'assignees'));
    }

    /**
     * Display the Kanban Board view (Task T233).
     */
    public function kanban(Request $request): View
    {
        $query = Task::with(['project', 'assignee', 'checklists']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%");
            });
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $allTasks = $query->latest('id')->get();

        $columns = [
            TaskStatus::TODO->value => $allTasks->where('status', TaskStatus::TODO),
            TaskStatus::IN_PROGRESS->value => $allTasks->where('status', TaskStatus::IN_PROGRESS),
            TaskStatus::IN_REVIEW->value => $allTasks->where('status', TaskStatus::IN_REVIEW),
            TaskStatus::BLOCKED->value => $allTasks->where('status', TaskStatus::BLOCKED),
            TaskStatus::DONE->value => $allTasks->where('status', TaskStatus::DONE),
        ];

        $projects = Project::whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::CANCELLED->value])
            ->orderBy('name')
            ->get();
        $assignees = User::with(['employee', 'teamMemberships.team'])
            ->where('is_active', true)
            ->whereNotIn('role', [UserRole::CLIENT])
            ->orderBy('name')
            ->get();

        return view('manager.tasks.kanban', compact('columns', 'projects', 'assignees'));
    }

    /**
     * Show form for creating a new task.
     */
    public function create(Request $request): View
    {
        $projects = Project::whereNotIn('status', [ProjectStatus::CANCELLED->value])
            ->orderBy('name')
            ->get();

        $selectedProjectId = $request->input('project_id', $projects->first()?->id);
        $selectedProject = $selectedProjectId ? Project::find($selectedProjectId) : null;

        $milestones = $selectedProject ? $selectedProject->milestones : collect();
        $parentTasks = $selectedProject ? $selectedProject->tasks()->whereNull('parent_id')->orderBy('title')->get() : collect();

        $assignees = User::with(['employee', 'teamMemberships.team'])
            ->where('is_active', true)
            ->whereNotIn('role', [UserRole::CLIENT])
            ->orderBy('name')
            ->get();

        return view('manager.tasks.create', compact('projects', 'selectedProject', 'milestones', 'parentTasks', 'assignees'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'task_code' => ['required', 'string', 'max:50', 'unique:tasks,task_code'],
            'milestone_id' => ['nullable', 'exists:project_milestones,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_pattern' => ['nullable', 'in:daily,weekly,monthly'],
            'recurrence_end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $validated['team_id'] = $project->team_id;
        $validated['created_by'] = Auth::id();
        $validated['estimated_hours'] = $validated['estimated_hours'] ?? 0;
        $validated['is_recurring'] = $request->boolean('is_recurring');

        if ($validated['status'] === TaskStatus::DONE->value) {
            $validated['completed_at'] = now();
        }

        $task = Task::create($validated);

        // Record history
        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'task.created',
            'details' => "Task '{$task->title}' ({$task->task_code}) was created.",
        ]);

        $this->auditLogger->logProject(
            action: 'task.created',
            projectId: $project->id,
            afterValues: $task->toArray(),
            description: "Task '{$task->title}' ({$task->task_code}) created."
        );

        // Notify assignee on task creation
        if ($task->assigned_to) {
            $task->load(['project', 'assignee']);
            $assigner = Auth::user();

            $this->notificationService->notifyTaskAssigned($task, $task->assignee, $assigner);

            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'action' => 'task.assigned',
                'field_name' => 'assigned_to',
                'new_value' => (string) $task->assigned_to,
                'details' => "Task assigned to {$task->assignee->name}.",
            ]);

            $this->auditLogger->logProject(
                action: 'task.assigned',
                projectId: $project->id,
                afterValues: ['task_id' => $task->id, 'assigned_to' => $task->assigned_to, 'assignee_name' => $task->assignee->name],
                description: "Task '{$task->title}' assigned to {$task->assignee->name}."
            );
        }

        return redirect()->route('manager.tasks.show', $task)
            ->with('success', "Task '{$task->title}' created successfully.");
    }

    /**
     * Display the specified task details.
     */
    public function show(Task $task): View
    {
        $task->load([
            'project.client',
            'milestone',
            'parent',
            'subtasks.assignee',
            'team',
            'assignee.employee',
            'blockingTasks',
            'dependentTasks',
            'checklists.completedBy',
            'comments.user',
            'attachments.user',
            'histories.user',
            'creator',
        ]);

        $blockers = $this->dependencyService->getUnresolvedBlockers($task);
        $isBlocked = $blockers->isNotEmpty();

        // Tasks in the same project available to add as dependencies
        $existingDepIds = $task->dependencies->pluck('depends_on_task_id')->push($task->id)->toArray();
        $availableDependencyTasks = Task::where('project_id', $task->project_id)
            ->whereNotIn('id', $existingDepIds)
            ->orderBy('title')
            ->get();

        return view('manager.tasks.show', compact('task', 'blockers', 'isBlocked', 'availableDependencyTasks'));
    }

    /**
     * Show form for editing task.
     */
    public function edit(Task $task): View
    {
        $projects = Project::whereNotIn('status', [ProjectStatus::CANCELLED->value])
            ->orderBy('name')
            ->get();
        $milestones = $task->project->milestones;
        $parentTasks = $task->project->tasks()->whereNull('parent_id')->where('id', '!=', $task->id)->orderBy('title')->get();
        $assignees = User::with(['employee', 'teamMemberships.team'])
            ->where('is_active', true)
            ->whereNotIn('role', [UserRole::CLIENT])
            ->orderBy('name')
            ->get();

        return view('manager.tasks.edit', compact('task', 'projects', 'milestones', 'parentTasks', 'assignees'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'task_code' => ['required', 'string', 'max:50', Rule::unique('tasks', 'task_code')->ignore($task->id)],
            'milestone_id' => ['nullable', 'exists:project_milestones,id'],
            'parent_id' => ['nullable', 'exists:tasks,id', Rule::notIn([$task->id])],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_pattern' => ['nullable', 'in:daily,weekly,monthly'],
            'recurrence_end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');

        // If status changed to done
        if ($validated['status'] === TaskStatus::DONE->value && $task->status !== TaskStatus::DONE) {
            // Blocker check (Task T228)
            $blockers = $this->dependencyService->getUnresolvedBlockers($task);
            if ($blockers->isNotEmpty()) {
                return back()->with('error', "Cannot complete task! Blocked by {$blockers->count()} unresolved dependencies.")->withInput();
            }
            $validated['completed_at'] = now();

            // Trigger recurrence if recurring
            if ($task->is_recurring) {
                $this->recurringService->generateNextOccurrence($task);
            }
        } elseif ($validated['status'] !== TaskStatus::DONE->value) {
            $validated['completed_at'] = null;
        }

        $before = $task->toArray();
        $oldAssignedTo = $task->assigned_to;
        $task->update($validated);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'task.updated',
            'details' => 'Task parameters and details were updated.',
        ]);

        $this->auditLogger->logProject(
            action: 'task.updated',
            projectId: $task->project_id,
            beforeValues: $before,
            afterValues: $task->toArray(),
            description: "Task '{$task->title}' ({$task->task_code}) updated."
        );

        // Handle assignment / reassignment notifications
        $newAssignedTo = $task->assigned_to;
        if ($newAssignedTo && (int) $newAssignedTo !== (int) ($oldAssignedTo ?? 0)) {
            $task->load(['project', 'assignee']);
            $assigner = Auth::user();

            $this->notificationService->notifyTaskAssigned($task, $task->assignee, $assigner);

            $oldAssigneeName = $oldAssignedTo ? (User::find($oldAssignedTo)?->name ?? 'Unknown') : 'Unassigned';

            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'action' => 'task.reassigned',
                'field_name' => 'assigned_to',
                'old_value' => (string) ($oldAssignedTo ?? ''),
                'new_value' => (string) $newAssignedTo,
                'details' => "Task reassigned from {$oldAssigneeName} to {$task->assignee->name}.",
            ]);

            $this->auditLogger->logProject(
                action: 'task.reassigned',
                projectId: $task->project_id,
                beforeValues: ['assigned_to' => $oldAssignedTo, 'assignee_name' => $oldAssigneeName],
                afterValues: ['assigned_to' => $newAssignedTo, 'assignee_name' => $task->assignee->name],
                description: "Task '{$task->title}' reassigned from {$oldAssigneeName} to {$task->assignee->name}."
            );
        }

        return redirect()->route('manager.tasks.show', $task)
            ->with('success', "Task updated successfully.");
    }

    /**
     * Quick status transition for task (with blocker enforcement).
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        $newStatus = TaskStatus::from($validated['status']);

        // Blocker enforcement for in_progress and done (Task T228)
        if (in_array($newStatus, [TaskStatus::IN_PROGRESS, TaskStatus::DONE])) {
            $blockers = $this->dependencyService->getUnresolvedBlockers($task);
            if ($blockers->isNotEmpty()) {
                // Auto transition to blocked status or reject
                $task->status = TaskStatus::BLOCKED;
                $task->save();

                TaskHistory::create([
                    'task_id' => $task->id,
                    'user_id' => Auth::id(),
                    'action' => 'task.blocked',
                    'details' => "Task was placed into Blocked status due to {$blockers->count()} unresolved dependencies.",
                ]);

                return back()->with('error', "Task is blocked by {$blockers->count()} unresolved dependencies: {$blockers->pluck('title')->implode(', ')}");
            }
        }

        $oldStatus = $task->status;
        $task->status = $newStatus;

        if ($newStatus === TaskStatus::DONE) {
            $task->completed_at = now();
            if ($task->is_recurring) {
                $this->recurringService->generateNextOccurrence($task);
            }
        } else {
            $task->completed_at = null;
        }

        $task->save();

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'task.status_changed',
            'old_value' => $oldStatus->value,
            'new_value' => $newStatus->value,
            'details' => "Task status changed from {$oldStatus->label()} to {$newStatus->label()}.",
        ]);

        $this->auditLogger->logProject(
            action: 'task.status_changed',
            projectId: $task->project_id,
            afterValues: ['task_id' => $task->id, 'status' => $task->status->value],
            description: "Task '{$task->title}' status set to {$task->status->label()}."
        );

        return back()->with('success', "Task status updated to {$task->status->label()}.");
    }

    /**
     * Remove the specified task (Soft delete).
     */
    public function destroy(Task $task): RedirectResponse
    {
        $before = $task->toArray();
        $title = $task->title;
        $projectId = $task->project_id;
        $task->delete();

        $this->auditLogger->logProject(
            action: 'task.deleted',
            projectId: $projectId,
            beforeValues: $before,
            description: "Task '{$title}' was deleted."
        );

        return redirect()->route('manager.tasks.index')
            ->with('success', "Task '{$title}' deleted successfully.");
    }

    // ==========================================
    // Checklists (Task T230)
    // ==========================================

    public function storeChecklist(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $order = ($task->checklists()->max('order') ?? 0) + 1;

        $checklist = TaskChecklist::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'is_completed' => false,
            'order' => $order,
        ]);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'checklist.added',
            'details' => "Checklist item '{$checklist->title}' added.",
        ]);

        return back()->with('success', 'Checklist item added.');
    }

    public function toggleChecklist(Task $task, TaskChecklist $checklist): RedirectResponse
    {
        $checklist->is_completed = !$checklist->is_completed;
        $checklist->completed_by = $checklist->is_completed ? Auth::id() : null;
        $checklist->completed_at = $checklist->is_completed ? now() : null;
        $checklist->save();

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => $checklist->is_completed ? 'checklist.completed' : 'checklist.reopened',
            'details' => "Checklist item '{$checklist->title}' marked as " . ($checklist->is_completed ? 'completed' : 'incomplete') . ".",
        ]);

        return back()->with('success', 'Checklist item updated.');
    }

    public function destroyChecklist(Task $task, TaskChecklist $checklist): RedirectResponse
    {
        $checklist->delete();
        return back()->with('success', 'Checklist item removed.');
    }

    // ==========================================
    // Dependencies & Circular Prevention (Task T228)
    // ==========================================

    public function storeDependency(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'depends_on_task_id' => ['required', 'exists:tasks,id', Rule::notIn([$task->id])],
            'dependency_type' => ['required', 'in:blocks,relates_to'],
        ]);

        $dependsOnTaskId = (int) $validated['depends_on_task_id'];

        // Circular dependency check (Task T228)
        if ($this->dependencyService->createsCycle($task->id, $dependsOnTaskId)) {
            return back()->with('error', 'Circular dependency detected! This link would cause an infinite dependency loop.');
        }

        if (TaskDependency::where('task_id', $task->id)->where('depends_on_task_id', $dependsOnTaskId)->exists()) {
            return back()->with('error', 'This dependency relationship already exists.');
        }

        $dependency = TaskDependency::create([
            'task_id' => $task->id,
            'depends_on_task_id' => $dependsOnTaskId,
            'dependency_type' => $validated['dependency_type'],
        ]);

        $targetTask = Task::find($dependsOnTaskId);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'dependency.added',
            'details' => "Added dependency on task #{$targetTask?->task_code} ({$targetTask?->title}).",
        ]);

        return back()->with('success', "Dependency added successfully.");
    }

    public function destroyDependency(Task $task, TaskDependency $dependency): RedirectResponse
    {
        $targetTask = $dependency->dependsOnTask;
        $dependency->delete();

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'dependency.removed',
            'details' => "Removed dependency on task #{$targetTask?->task_code}.",
        ]);

        return back()->with('success', 'Dependency removed.');
    }

    // ==========================================
    // Internal Comments (Task T230)
    // ==========================================

    public function storeComment(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'comment_type' => ['nullable', \Illuminate\Validation\Rule::enum(\App\Enums\TaskCommentType::class)],
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
            'comment_type' => $validated['comment_type'] ?? \App\Enums\TaskCommentType::GENERAL->value,
            'is_internal' => true, // Strict client isolation
        ]);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'comment.added',
            'details' => 'Added an internal task comment.',
        ]);

        return back()->with('success', 'Comment posted.');
    }

    public function destroyComment(Task $task, TaskComment $comment): RedirectResponse
    {
        $comment->delete();
        return back()->with('success', 'Comment deleted.');
    }

    // ==========================================
    // Task Attachments (Task T231)
    // ==========================================

    public function storeAttachment(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB limit
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();
        $mime = $file->getMimeType();

        // Isolated path: projects/{project_id}/tasks/{task_id}/
        $path = $file->store("projects/{$task->project_id}/tasks/{$task->id}", 'local');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $size,
            'mime_type' => $mime,
            'is_internal' => true,
        ]);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'attachment.uploaded',
            'details' => "Uploaded attachment '{$originalName}'.",
        ]);

        return back()->with('success', "File '{$originalName}' uploaded successfully.");
    }

    public function downloadAttachment(Task $task, TaskAttachment $attachment): StreamedResponse
    {
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    public function destroyAttachment(Task $task, TaskAttachment $attachment): RedirectResponse
    {
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $attachment->delete();

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'attachment.deleted',
            'details' => "Deleted attachment '{$attachment->file_name}'.",
        ]);

        return back()->with('success', 'Attachment deleted.');
    }
}
