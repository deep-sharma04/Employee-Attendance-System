<?php

namespace App\Services\Notification;

use App\Mail\LeaveStatusMail;
use App\Mail\PayslipFinalizedMail;
use App\Mail\ProjectNotificationMail;
use App\Models\Document;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    /**
     * T262 & T266: Dispatch notification across multiple enabled channels.
     * Channels supported: 'in_app', 'email', 'web_push' (No WhatsApp in V1).
     */
    public function send(
        User|int $user,
        string $category,
        string $title,
        string $message,
        string $type = 'general',
        array $data = []
    ): array {
        $userModel = $user instanceof User ? $user : User::find($user);
        if (!$userModel) {
            return [];
        }

        $results = [
            'in_app' => null,
            'email' => null,
            'web_push' => null,
        ];

        $targetUrl = $data['url'] ?? null;
        if ($targetUrl && !str_starts_with($targetUrl, 'http')) {
            $targetUrl = url($targetUrl);
        }

        // 1. In-App Notification Channel
        if (NotificationPreference::isChannelEnabled($userModel, $category, 'in_app')) {
            try {
                $notification = Notification::create([
                    'user_id' => $userModel->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'data' => $data,
                    'read_at' => null,
                ]);
                $results['in_app'] = $notification;
                $this->logDispatch($userModel, 'in_app', $type, $category, 'sent', null, $data);
            } catch (\Throwable $e) {
                Log::error("Failed to create in-app notification for user {$userModel->id}: " . $e->getMessage());
                $this->logDispatch($userModel, 'in_app', $type, $category, 'failed', $e->getMessage(), $data);
            }
        } else {
            $this->logDispatch($userModel, 'in_app', $type, $category, 'skipped', 'Disabled by user preference', $data);
        }

        // 2. Email Notification Channel
        if (NotificationPreference::isChannelEnabled($userModel, $category, 'email') && !empty($userModel->email)) {
            try {
                Mail::to($userModel->email)->send(new ProjectNotificationMail(
                    recipient: $userModel,
                    title: $title,
                    bodyMessage: $message,
                    actionUrl: $targetUrl,
                    category: $category,
                    data: $data
                ));
                $results['email'] = true;
                $this->logDispatch($userModel, 'email', $type, $category, 'sent', null, $data);
            } catch (\Throwable $e) {
                Log::warning("Failed to send notification email to {$userModel->email}: " . $e->getMessage());
                $this->logDispatch($userModel, 'email', $type, $category, 'failed', $e->getMessage(), $data);
            }
        } else {
            $this->logDispatch($userModel, 'email', $type, $category, 'skipped', 'Disabled by user preference or missing email', $data);
        }

        // 3. Web-Push Notification Channel
        if (NotificationPreference::isChannelEnabled($userModel, $category, 'web_push')) {
            // Record web push dispatch payload (V1 web push handler)
            $results['web_push'] = true;
            $this->logDispatch($userModel, 'web_push', $type, $category, 'sent', null, [
                'payload' => [
                    'title' => $title,
                    'body' => $message,
                    'url' => $targetUrl,
                ],
            ]);
        } else {
            $this->logDispatch($userModel, 'web_push', $type, $category, 'skipped', 'Disabled by user preference', $data);
        }

        return $results;
    }

    /**
     * T266: Record immutable notification dispatch audit log.
     */
    protected function logDispatch(
        User $user,
        string $channel,
        string $type,
        string $category,
        string $status,
        ?string $errorMessage = null,
        array $data = []
    ): void {
        if (!Schema::hasTable('notification_dispatches')) {
            return;
        }

        NotificationDispatch::create([
            'user_id' => $user->id,
            'recipient_email' => $user->email,
            'notification_type' => $type,
            'category' => $category,
            'channel' => $channel,
            'status' => $status,
            'error_message' => $errorMessage,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    /**
     * Legacy In-App Notify Helper.
     */
    public function notify(
        User|int $user,
        string $title,
        string $message,
        string $type = 'general',
        array $data = []
    ): ?Notification {
        $results = $this->send($user, 'task_assignment', $title, $message, $type, $data);
        return $results['in_app'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | T264: Project Notification Triggers
    |--------------------------------------------------------------------------
    */

    /**
     * Trigger: Notify assignee when a task is assigned.
     */
    public function notifyTaskAssigned(Task $task, User $assignee, ?User $assigner = null): array
    {
        $assignerName = $assigner ? $assigner->name : 'System';
        $projectName = $task->project ? $task->project->name : 'Project';
        $priorityLabel = $task->priority ? $task->priority->value : 'medium';

        $title = "New Task Assigned: {$task->title}";
        $message = "You have been assigned to task '{$task->title}' ({$task->task_code}) in {$projectName} by {$assignerName}.";
        
        if ($task->due_date) {
            $message .= " Due date: " . $task->due_date->format('M d, Y') . ".";
        }

        if ($task->priority) {
            $message .= " Priority: " . ucfirst($priorityLabel) . ".";
        }

        // Generate the correct task URL based on the assignee's role
        $taskUrl = $this->resolveTaskUrl($task, $assignee);

        return $this->send(
            user: $assignee,
            category: 'task_assignment',
            title: $title,
            message: $message,
            type: 'task_assigned',
            data: [
                'task_id' => $task->id,
                'task_code' => $task->task_code,
                'project_id' => $task->project_id,
                'project_name' => $projectName,
                'assigner_name' => $assignerName,
                'due_date' => $task->due_date?->toDateString(),
                'priority' => $priorityLabel,
                'is_recurring' => $task->is_recurring,
                'url' => $taskUrl,
            ]
        );
    }

    /**
     * Resolve the correct task detail URL based on the user's role.
     */
    protected function resolveTaskUrl(Task $task, User $user): ?string
    {
        if ($user->isSuperAdmin() || $user->isManager()) {
            return route('manager.tasks.show', $task);
        }

        if ($user->isTeamLead()) {
            return route('team-lead.tasks.show', $task);
        }

        if ($user->isEmployee()) {
            return route('employee.tasks.show', $task);
        }

        return null;
    }

    /**
     * Trigger: Notify assignee when task deadline is approaching (within 48h).
     */
    public function notifyTaskDeadlineApproaching(Task $task): ?array
    {
        $assignee = $task->assignee;
        if (!$assignee || !$task->due_date) {
            return null;
        }

        $title = "Upcoming Deadline: {$task->title}";
        $message = "Task '{$task->title}' ({$task->task_code}) is due on " . $task->due_date->format('M d, Y') . ". Please ensure your work is submitted on time.";

        return $this->send(
            user: $assignee,
            category: 'deadlines',
            title: $title,
            message: $message,
            type: 'task_deadline_approaching',
            data: [
                'task_id' => $task->id,
                'task_code' => $task->task_code,
                'due_date' => $task->due_date->toDateString(),
            ]
        );
    }

    /**
     * Trigger: Notify assignee and manager when task is overdue.
     */
    public function notifyTaskOverdue(Task $task): array
    {
        $results = [];
        $title = "Task Overdue: {$task->title}";
        $message = "Task '{$task->title}' ({$task->task_code}) passed its deadline on " . ($task->due_date ? $task->due_date->format('M d, Y') : 'past date') . " and is currently overdue.";

        if ($task->assignee) {
            $results['assignee'] = $this->send(
                user: $task->assignee,
                category: 'deadlines',
                title: $title,
                message: $message,
                type: 'task_overdue',
                data: [
                    'task_id' => $task->id,
                    'task_code' => $task->task_code,
                    'due_date' => $task->due_date ? $task->due_date->toDateString() : null,
                ]
            );
        }

        if ($task->project && $task->project->manager) {
            $results['manager'] = $this->send(
                user: $task->project->manager,
                category: 'deadlines',
                title: "Overdue Task Alert: {$task->title}",
                message: "Assigned employee {$task->assignee?->name} has an overdue task '{$task->title}' in project '{$task->project->name}'.",
                type: 'task_overdue_manager',
                data: [
                    'task_id' => $task->id,
                    'project_id' => $task->project_id,
                ]
            );
        }

        return $results;
    }

    /**
     * Trigger: Notify manager/approver when a timesheet is submitted.
     */
    public function notifyTimesheetSubmitted(Timesheet $timesheet): array
    {
        $employee = $timesheet->employee;
        $employeeName = $employee ? $employee->full_name : 'An employee';
        $title = "Timesheet Submitted for Approval";
        $message = "{$employeeName} has submitted timesheet #{$timesheet->id} ({$timesheet->total_hours} hrs) for period {$timesheet->start_date->format('M d')} - {$timesheet->end_date->format('M d, Y')}.";

        $results = [];

        // Notify Project Manager or Team Lead
        $approver = $timesheet->approver;
        if (!$approver && $employee && $employee->primaryTeam) {
            $approver = $employee->primaryTeam->teamLead ?? $employee->primaryTeam->manager;
        }

        if ($approver) {
            $results['approver'] = $this->send(
                user: $approver,
                category: 'timesheets',
                title: $title,
                message: $message,
                type: 'timesheet_submitted',
                data: [
                    'timesheet_id' => $timesheet->id,
                    'employee_id' => $timesheet->employee_id,
                    'total_hours' => $timesheet->total_hours,
                    'url' => route('manager.timesheets.show', $timesheet),
                ]
            );
        }

        return $results;
    }

    /**
     * Trigger: Notify employee on timesheet status decision (approved/rejected/returned).
     */
    public function notifyTimesheetStatus(Timesheet $timesheet, string $status, ?string $reason = null): ?array
    {
        $user = $timesheet->user ?? ($timesheet->employee ? $timesheet->employee->user : null);
        if (!$user) {
            return null;
        }

        $statusLabel = match ($status) {
            'approved' => 'Approved',
            'returned' => 'Returned for Revision',
            'rejected' => 'Rejected',
            default => ucfirst($status),
        };

        $title = "Timesheet {$statusLabel}";
        $message = "Your timesheet for {$timesheet->start_date->format('M d')} - {$timesheet->end_date->format('M d, Y')} ({$timesheet->total_hours} hrs) has been {$status}.";
        
        if (!empty($reason)) {
            $message .= " Note / Remarks: {$reason}";
        }

        return $this->send(
            user: $user,
            category: 'timesheets',
            title: $title,
            message: $message,
            type: "timesheet_{$status}",
            data: [
                'timesheet_id' => $timesheet->id,
                'status' => $status,
                'reason' => $reason,
                'url' => route('employee.timesheets.show', $timesheet),
            ]
        );
    }

    /**
     * Trigger: Notify team when a project milestone is completed.
     */
    public function notifyMilestoneCompleted(ProjectMilestone $milestone): array
    {
        $project = $milestone->project;
        $title = "Milestone Achieved: {$milestone->title}";
        $message = "Milestone '{$milestone->title}' in project '{$project?->name}' has been marked as completed.";

        $results = [];

        if ($project && $project->manager) {
            $results['manager'] = $this->send(
                user: $project->manager,
                category: 'project_milestones',
                title: $title,
                message: $message,
                type: 'milestone_completed',
                data: [
                    'milestone_id' => $milestone->id,
                    'project_id' => $project->id,
                    'url' => route('manager.projects.show', $project),
                ]
            );
        }

        return $results;
    }

    /**
     * Trigger: Notify manager and team lead when project health changes.
     */
    public function notifyProjectHealthChanged(Project $project, string $oldHealth, string $newHealth): array
    {
        $title = "Project Health Alert: {$project->name}";
        $oldHealthLabel = strtoupper(str_replace('_', ' ', $oldHealth));
        $newHealthLabel = strtoupper(str_replace('_', ' ', $newHealth));
        $message = "Project '{$project->name}' ({$project->code}) health shifted from {$oldHealthLabel} to {$newHealthLabel}.";

        $results = [];

        if ($project->manager) {
            $results['manager'] = $this->send(
                user: $project->manager,
                category: 'project_milestones',
                title: $title,
                message: $message,
                type: 'project_health_changed',
                data: [
                    'project_id' => $project->id,
                    'old_health' => $oldHealth,
                    'new_health' => $newHealth,
                    'url' => route('manager.projects.show', $project),
                ]
            );
        }

        return $results;
    }

    /**
     * T265: Send daily morning work summary to user.
     */
    public function sendDailySummary(User $user, array $summaryData): array
    {
        $assignedCount = $summaryData['assigned_count'] ?? 0;
        $overdueCount = $summaryData['overdue_count'] ?? 0;
        $pendingApprovalsCount = $summaryData['pending_approvals_count'] ?? 0;

        $title = "Daily Work & Tasks Summary - " . now()->format('M d, Y');
        $message = "Good morning, {$user->name}! Here is your active work summary:\n"
                 . "- Active Assigned Tasks: {$assignedCount}\n"
                 . "- Overdue Tasks: {$overdueCount}\n";

        if ($pendingApprovalsCount > 0) {
            $message .= "- Timesheets Pending Approval: {$pendingApprovalsCount}\n";
        }

        return $this->send(
            user: $user,
            category: 'daily_summary',
            title: $title,
            message: trim($message),
            type: 'daily_summary',
            data: $summaryData
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy HR Notifiers (Leaves, Payslips, Documents)
    |--------------------------------------------------------------------------
    */

    public function notifyLeaveStatus(LeaveRequest $leave, string $status, ?string $reason = null): ?Notification
    {
        $employee = $leave->employee;
        if (!$employee || !$employee->user_id) {
            return null;
        }

        $leaveName = $leave->leaveType?->name ?? 'Leave';
        $statusLabel = ucfirst($status);
        $title = "Leave Request {$statusLabel}";
        
        $dates = $leave->start_date->format('M d, Y');
        if ($leave->start_date->ne($leave->end_date)) {
            $dates .= ' to ' . $leave->end_date->format('M d, Y');
        }

        $message = "Your {$leaveName} request for {$dates} ({$leave->total_days} day(s)) has been {$status}.";
        if (!empty($reason)) {
            $message .= " Reason / Notes: {$reason}";
        }

        // Send In-App notification
        $inApp = $this->notify(
            $employee->user_id,
            $title,
            $message,
            $status === 'approved' ? 'leave_approved' : 'leave_rejected',
            [
                'leave_id' => $leave->id,
                'status' => $status,
                'rejection_reason' => $reason,
                'url' => '/employee/leaves',
            ]
        );

        // Send Specialized LeaveStatusMail
        if ($employee->user && !empty($employee->user->email)) {
            try {
                Mail::to($employee->user->email)->send(new LeaveStatusMail($leave, $status, $reason));
                $this->logDispatch($employee->user, 'email', $status === 'approved' ? 'leave_approved' : 'leave_rejected', 'security', 'sent', null, ['leave_id' => $leave->id]);
            } catch (\Throwable $e) {
                Log::warning("Failed to send leave status email: " . $e->getMessage());
                $this->logDispatch($employee->user, 'email', $status === 'approved' ? 'leave_approved' : 'leave_rejected', 'security', 'failed', $e->getMessage(), ['leave_id' => $leave->id]);
            }
        }

        return $inApp;
    }

    public function notifyPayslipAvailable(Payroll $payroll): ?Notification
    {
        $employee = $payroll->employee;
        if (!$employee || !$employee->user_id) {
            return null;
        }

        $month = $payroll->payroll_month ?? $payroll->month;
        $year = $payroll->payroll_year ?? $payroll->year;
        $monthName = date('F Y', mktime(0, 0, 0, (int) $month, 1, (int) $year));
        $title = "Payslip Available ({$monthName})";
        $message = "Your payslip for {$monthName} has been finalized. Net Salary: " . number_format($payroll->net_salary, 2) . ". You may now view or download your official payslip.";

        // Send In-App notification
        $inApp = $this->notify(
            $employee->user_id,
            $title,
            $message,
            'payslip_finalized',
            [
                'payroll_id' => $payroll->id,
                'month' => $month,
                'year' => $year,
                'net_salary' => $payroll->net_salary,
                'url' => '/employee/payslips',
            ]
        );

        // Send Specialized PayslipFinalizedMail
        if ($employee->user && !empty($employee->user->email)) {
            try {
                Mail::to($employee->user->email)->send(new PayslipFinalizedMail($payroll));
                $this->logDispatch($employee->user, 'email', 'payslip_finalized', 'security', 'sent', null, ['payroll_id' => $payroll->id]);
            } catch (\Throwable $e) {
                Log::warning("Failed to send payslip email: " . $e->getMessage());
                $this->logDispatch($employee->user, 'email', 'payslip_finalized', 'security', 'failed', $e->getMessage(), ['payroll_id' => $payroll->id]);
            }
        }

        return $inApp;
    }

    public function notifyDocumentStatus(Document $doc, string $status, ?string $reason = null): ?Notification
    {
        $employee = $doc->employee;
        if (!$employee || !$employee->user_id) {
            return null;
        }

        $docTitle = $doc->title ?: ($doc->documentType?->name ?? 'Document');
        $statusLabel = ucfirst($status);
        $title = "Document {$statusLabel}";

        $message = "Your document '{$docTitle}' has been {$status}.";
        if (!empty($reason)) {
            $message .= " Remarks: {$reason}";
        }

        return $this->notify(
            $employee->user_id,
            $title,
            $message,
            $status === 'verified' ? 'document_verified' : 'document_rejected',
            [
                'document_id' => $doc->id,
                'status' => $status,
                'rejection_reason' => $reason,
                'url' => '/employee/profile',
            ]
        );
    }

    public function getUnreadCount(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        if (!Schema::hasTable('notifications')) {
            return 0;
        }

        return Notification::where('user_id', $userId)->unread()->count();
    }

    public function getRecentNotifications(User|int $user, int $limit = 10): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;
        if (!Schema::hasTable('notifications')) {
            return new Collection();
        }

        return Notification::where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markAllAsRead(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        if (Schema::hasTable('notifications')) {
            Notification::where('user_id', $userId)->unread()->update(['read_at' => now()]);
        }
    }
}
