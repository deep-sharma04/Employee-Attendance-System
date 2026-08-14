<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Enums\UserRole;
use App\Mail\ProjectNotificationMail;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Phase29NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected Employee $employee;
    protected Team $team;
    protected Project $project;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationService::class);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Manager Marcus',
            'email' => 'marcus.manager@hrm-test.com',
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::factory()->create([
            'name' => 'Lead Lucas',
            'email' => 'lucas.lead@hrm-test.com',
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'name' => 'Dev Dave',
            'email' => 'dave.dev@hrm-test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-DEV-001',
            'first_name' => 'Dave',
            'last_name' => 'Developer',
            'email' => $this->employeeUser->email,
            'department' => 'Engineering',
            'designation' => 'Senior Backend Engineer',
            'status' => EmployeeStatus::ACTIVE,
            'joining_date' => now()->subYear(),
            'monthly_salary' => 3520.00,
        ]);

        $this->team = Team::create([
            'name' => 'Core Systems Squad',
            'code' => 'SQD-CORE',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employee->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $client = Client::create([
            'company_name' => 'OmniCorp',
            'company_code' => 'CLT-OMNI',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->project = Project::create([
            'name' => 'Cloud Infrastructure Overhaul',
            'code' => 'PRJ-INFRA-01',
            'client_id' => $client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 15000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'created_by' => $this->manager->id,
        ]);
    }

    /**
     * T262: Extend Notification Channels (In-App, Email, Web-Push).
     */
    public function test_t262_multi_channel_notification_dispatch_in_app_email_web_push(): void
    {
        Mail::fake();

        $results = $this->notificationService->send(
            user: $this->employeeUser,
            category: 'task_assignment',
            title: 'Critical Security Patch Assigned',
            message: 'Please review and deploy the CVE mitigation immediately.',
            type: 'task_assigned',
            data: ['url' => '/employee/dashboard']
        );

        // 1. Verify in-app delivery
        $this->assertNotNull($results['in_app']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'title' => 'Critical Security Patch Assigned',
            'type' => 'task_assigned',
        ]);

        // 2. Verify email delivery
        $this->assertTrue($results['email']);
        Mail::assertSent(ProjectNotificationMail::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email)
                && $mail->title === 'Critical Security Patch Assigned';
        });

        // 3. Verify web-push payload recorded
        $this->assertTrue($results['web_push']);

        // 4. Verify dispatches logged across all 3 channels
        $this->assertDatabaseHas('notification_dispatches', [
            'user_id' => $this->employeeUser->id,
            'channel' => 'in_app',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notification_dispatches', [
            'user_id' => $this->employeeUser->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notification_dispatches', [
            'user_id' => $this->employeeUser->id,
            'channel' => 'web_push',
            'status' => 'sent',
        ]);
    }

    /**
     * T263: Notification Preferences and Mandatory Security Notification Enforcement.
     */
    public function test_t263_notification_preferences_management_and_mandatory_security(): void
    {
        Mail::fake();

        // 1. User loads preferences view
        $viewResponse = $this->actingAs($this->employeeUser)->get(route('notifications.preferences'));
        $viewResponse->assertOk();
        $viewResponse->assertViewIs('notifications.preferences');
        $viewResponse->assertSee('Delivery Channels Matrix');
        $viewResponse->assertSee('Security &amp; Account Alerts', false);

        // 2. User disables email for task assignments
        $postResponse = $this->actingAs($this->employeeUser)->post(route('notifications.preferences.update'), [
            'preferences' => [
                'task_assignment' => [
                    'in_app' => '1',
                    'email' => '0',
                    'web_push' => '1',
                ],
                'security' => [
                    'in_app' => '0', // Attempt to disable mandatory security
                    'email' => '0',
                ],
            ],
        ]);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        // Verify task_assignment email is disabled in DB
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->employeeUser->id,
            'category' => 'task_assignment',
            'channel' => 'email',
            'is_enabled' => false,
        ]);

        // 3. Dispatch task_assignment notification: In-App should send, Email should be SKIPPED
        $this->notificationService->send(
            user: $this->employeeUser,
            category: 'task_assignment',
            title: 'Task Assigned',
            message: 'A new task was assigned to you.',
            type: 'task_assigned'
        );

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_dispatches', [
            'user_id' => $this->employeeUser->id,
            'category' => 'task_assignment',
            'channel' => 'email',
            'status' => 'skipped',
        ]);

        // 4. Dispatch mandatory security notification: Must STILL send email regardless of preference
        $this->notificationService->send(
            user: $this->employeeUser,
            category: 'security',
            title: 'Password Changed Successfully',
            message: 'Your account password was updated.',
            type: 'security_alert'
        );

        Mail::assertSent(ProjectNotificationMail::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email)
                && $mail->title === 'Password Changed Successfully';
        });
    }

    /**
     * T264: Project Notification Triggers (Tasks, Deadlines, Timesheets, Milestones, Health).
     */
    public function test_t264_project_notification_triggers(): void
    {
        Mail::fake();

        // 1. Task Assigned trigger
        $task = Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-K8S-01',
            'title' => 'Deploy Kubernetes Cluster',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->notificationService->notifyTaskAssigned($task, $this->employeeUser, $this->manager);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'task_assigned',
        ]);

        // 2. Deadline Approaching trigger
        $this->notificationService->notifyTaskDeadlineApproaching($task);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'task_deadline_approaching',
        ]);

        // 3. Task Overdue trigger (notifies assignee and manager)
        $this->notificationService->notifyTaskOverdue($task);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'task_overdue',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'task_overdue_manager',
        ]);

        // 4. Timesheet Submitted trigger
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'period_type' => 'weekly',
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'total_hours' => 35.00,
            'status' => TimesheetStatus::SUBMITTED,
            'submitted_at' => now(),
            'approved_by' => $this->manager->id,
        ]);

        $this->notificationService->notifyTimesheetSubmitted($timesheet);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'timesheet_submitted',
        ]);

        // 5. Timesheet Approved trigger
        $this->notificationService->notifyTimesheetStatus($timesheet, 'approved');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'timesheet_approved',
        ]);

        // 6. Milestone Completed trigger
        $milestone = ProjectMilestone::create([
            'project_id' => $this->project->id,
            'title' => 'Cluster Provisioning Complete',
            'order' => 1,
            'status' => 'completed',
            'completed_at' => now(),
            'created_by' => $this->manager->id,
        ]);

        $this->notificationService->notifyMilestoneCompleted($milestone);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'milestone_completed',
        ]);

        // 7. Project Health Changed trigger
        $this->notificationService->notifyProjectHealthChanged($this->project, 'good', 'at_risk');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'project_health_changed',
        ]);
    }

    /**
     * T265: Daily Notification Summary Console Command.
     */
    public function test_t265_daily_summary_console_command(): void
    {
        Mail::fake();

        // Create tasks assigned to employee (1 active, 1 overdue)
        Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-SUM-01',
            'title' => 'Review Helm Charts',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::MEDIUM,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-SUM-02',
            'title' => 'Renew SSL Certificate',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::URGENT,
            'due_date' => now()->subDays(2)->toDateString(), // Overdue
        ]);

        // Run artisan command
        $this->artisan('notifications:send-daily-summary', ['--user' => $this->employeeUser->id])
            ->expectsOutputToContain('Daily notification summary dispatched')
            ->assertSuccessful();

        // Verify summary notification created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'daily_summary',
        ]);

        $notification = Notification::where('user_id', $this->employeeUser->id)->where('type', 'daily_summary')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Active Assigned Tasks: 2', $notification->message);
        $this->assertStringContainsString('Overdue Tasks: 1', $notification->message);
    }

    /**
     * T266: Notification Dispatch Audit Logging and Dispatches Viewer.
     */
    public function test_t266_notification_dispatch_audit_logging_and_viewer(): void
    {
        // 1. Trigger notification to generate dispatch logs
        $this->notificationService->send(
            user: $this->manager,
            category: 'project_milestones',
            title: 'Project Audit Complete',
            message: 'Annual compliance review completed with zero findings.',
            type: 'milestone_completed'
        );

        // 2. Manager views dispatch logs
        $response = $this->actingAs($this->manager)->get(route('notifications.dispatches'));

        $response->assertOk();
        $response->assertViewIs('notifications.dispatches');
        $response->assertViewHas('dispatches');
        $response->assertSee('milestone_completed');
        $response->assertSee('marcus.manager@hrm-test.com');
        $response->assertSee('PROJECT MILESTONES');
    }
}
