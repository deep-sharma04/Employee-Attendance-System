<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Models\NotificationPreference;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class SendDailyNotificationSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-daily-summary {--user= : Optional target user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily morning work summaries of assigned tasks, overdue items, and pending approvals.';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $userId = $this->option('user');

        $usersQuery = User::where('is_active', true);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        $users = $usersQuery->get();
        $dispatchedCount = 0;
        $today = now()->toDateString();

        foreach ($users as $user) {
            // Check if daily summary is enabled on any channel
            $inAppEnabled = NotificationPreference::isChannelEnabled($user, 'daily_summary', 'in_app');
            $emailEnabled = NotificationPreference::isChannelEnabled($user, 'daily_summary', 'email');
            $pushEnabled = NotificationPreference::isChannelEnabled($user, 'daily_summary', 'web_push');

            if (!$inAppEnabled && !$emailEnabled && !$pushEnabled) {
                continue;
            }

            // 1. Active assigned tasks
            $activeTasks = Task::where('assigned_to', $user->id)
                ->whereIn('status', [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::IN_REVIEW, TaskStatus::BLOCKED])
                ->get();

            // 2. Overdue tasks
            $overdueCount = $activeTasks->filter(function (Task $t) use ($today) {
                return $t->due_date && $t->due_date->toDateString() < $today;
            })->count();

            // 3. Pending approvals (if Manager or Team Lead)
            $pendingApprovalsCount = 0;
            if ($user->isSuperAdmin() || $user->isManager()) {
                $pendingApprovalsCount = Timesheet::where('status', TimesheetStatus::SUBMITTED)->count();
            } elseif ($user->isTeamLead()) {
                $team = $user->ledTeams()->first();
                if ($team) {
                    $memberUserIds = $team->members()->pluck('users.id');
                    $pendingApprovalsCount = Timesheet::whereIn('user_id', $memberUserIds)
                        ->where('status', TimesheetStatus::SUBMITTED)
                        ->count();
                }
            }

            // Dispatch if there is relevant activity
            if ($activeTasks->count() > 0 || $overdueCount > 0 || $pendingApprovalsCount > 0) {
                $notificationService->sendDailySummary($user, [
                    'assigned_count' => $activeTasks->count(),
                    'overdue_count' => $overdueCount,
                    'pending_approvals_count' => $pendingApprovalsCount,
                    'top_tasks' => $activeTasks->take(5)->pluck('title')->toArray(),
                ]);
                $dispatchedCount++;
            }
        }

        $this->info("Daily notification summary dispatched to {$dispatchedCount} user(s).");
        return Command::SUCCESS;
    }
}
