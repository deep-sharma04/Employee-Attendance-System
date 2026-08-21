<?php

namespace App\Http\Controllers\Employee;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeTaskController extends Controller
{
    /**
     * Display tasks assigned to the current employee.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Task::with(['project', 'assignee', 'milestone', 'recurringParent'])
            ->where('assigned_to', $user->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($request->boolean('overdue')) {
            $query->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now()->toDateString());
        }

        $tasks = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => Task::where('assigned_to', $user->id)->count(),
            'todo' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::TODO->value)->count(),
            'in_progress' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::IN_PROGRESS->value)->count(),
            'in_review' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::IN_REVIEW->value)->count(),
            'done' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::DONE->value)->count(),
            'overdue' => Task::where('assigned_to', $user->id)
                ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('employee.tasks.index', compact('tasks', 'stats'));
    }

    /**
     * Display a single task detail (read-only for employee).
     */
    public function show(Task $task): View
    {
        $user = Auth::user();

        // Authorization: employee can only view tasks assigned to them
        // or tasks in projects they are a member of
        abort_unless(
            (int) $task->assigned_to === (int) $user->id
            || $task->project?->projectMembers()->where('user_id', $user->id)->exists()
            || ($task->team && $task->team->teamMembers()->where('user_id', $user->id)->exists()),
            403,
            'You are not authorized to view this task.'
        );

        $task->load([
            'project',
            'milestone',
            'assignee.employee',
            'recurringParent',
            'checklists',
            'comments.user',
            'attachments.user',
            'histories.user',
            'creator',
        ]);

        return view('employee.tasks.show', compact('task'));
    }

    /**
     * Display recurring task definitions assigned to the current employee.
     */
    public function recurring(Request $request): View
    {
        $user = Auth::user();

        $query = Task::with(['project', 'assignee', 'recurringOccurrences'])
            ->where('assigned_to', $user->id)
            ->recurringDefinitions();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%");
            });
        }

        $recurringTasks = $query->latest('id')->paginate(15)->withQueryString();

        return view('employee.tasks.recurring', compact('recurringTasks'));
    }

    /**
     * Update task status (assigned tasks).
     */
    public function updateStatus(Request $request, Task $task): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        abort_unless((int) $task->assigned_to === (int) $user->id, 403, 'You can only update tasks assigned to you.');

        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(TaskStatus::class)],
        ]);

        $oldStatus = $task->status;
        $newStatus = TaskStatus::tryFrom($validated['status']);

        if ($oldStatus === $newStatus) {
            return back();
        }

        if ($newStatus === TaskStatus::DONE) {
            $task->completed_at = now();
        } elseif ($oldStatus === TaskStatus::DONE) {
            $task->completed_at = null;
        }

        $task->status = $newStatus->value;
        $task->save();

        \App\Models\TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'task.status_changed',
            'old_value' => $oldStatus->value,
            'new_value' => $newStatus->value,
        ]);

        // Notifications
        $recipients = collect();

        // Team members
        if ($task->team) {
            $recipients = $recipients->merge($task->team->users);
        }

        // Manager (via Project -> Client? Or just the project's manager. Or team manager.)
        // By default, let's notify the creator if they are a manager/admin.
        if ($task->creator && $task->creator->id !== $user->id) {
            $recipients->push($task->creator);
        }
        
        // HR Admins
        $hrAdmins = \App\Models\User::whereIn('role', [\App\Enums\UserRole::HR_ADMIN->value, \App\Enums\UserRole::SUPER_ADMIN->value])->get();
        $recipients = $recipients->merge($hrAdmins);

        $recipients = $recipients->unique('id')->reject(fn ($u) => $u->id === $user->id);

        foreach ($recipients as $recipient) {
            \App\Models\Notification::create([
                'user_id' => $recipient->id,
                'type' => 'task.status_updated',
                'title' => "Task Status Updated: {$task->task_code}",
                'message' => "{$user->name} updated task '{$task->title}' status to {$newStatus->label()}.",
                'data' => [
                    'task_id' => $task->id,
                    'project_id' => $task->project_id,
                ],
            ]);

            \Illuminate\Support\Facades\Mail::to($recipient->email)
                ->queue(new \App\Mail\TaskStatusUpdatedMail($task, $user));
        }

        return back()->with('success', 'Task status updated successfully.');
    }

    /**
     * Store a comment on the task.
     */
    public function storeComment(Request $request, Task $task): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        abort_unless(
            (int) $task->assigned_to === (int) $user->id
            || $task->project?->projectMembers()->where('user_id', $user->id)->exists()
            || ($task->team && $task->team->teamMembers()->where('user_id', $user->id)->exists()),
            403,
            'You are not authorized to comment on this task.'
        );

        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'comment_type' => ['nullable', \Illuminate\Validation\Rule::enum(\App\Enums\TaskCommentType::class)],
        ]);

        \App\Models\TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => $validated['comment'],
            'comment_type' => $validated['comment_type'] ?? \App\Enums\TaskCommentType::GENERAL->value,
            'is_internal' => false,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }
}
