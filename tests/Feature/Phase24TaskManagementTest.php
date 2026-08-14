<?php

namespace Tests\Feature;

use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\TaskHistory;
use App\Models\Team;
use App\Models\User;
use App\Services\Task\OverdueTaskDetectionService;
use App\Services\Task\RecurringTaskService;
use App\Services\Task\TaskDependencyService;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase24TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser1;
    protected User $employeeUser2;
    protected User $clientUser;
    protected User $hrAdmin;
    protected Project $project;
    protected ProjectMilestone $milestone;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);
        Storage::fake('local');

        $shift = Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN_DAY',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Engineering Manager',
            'username' => 'engmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Lead Architect',
            'username' => 'leadarch',
            'email' => 'lead@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::create([
            'name' => 'HR Admin',
            'username' => 'hradmin',
            'email' => 'hr@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser1 = User::create([
            'name' => 'Alice Developer',
            'username' => 'alicedev',
            'email' => 'alice@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employeeUser1->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Alice',
            'last_name' => 'Developer',
            'email' => 'alice@example.com',
            'gender' => 'female',
            'date_of_birth' => '1995-05-15',
            'joining_date' => '2023-01-10',
            'department' => 'Engineering',
            'designation' => 'Fullstack Developer',
            'shift_id' => $shift->id,
            'status' => \App\Enums\EmployeeStatus::ACTIVE,
        ]);

        $this->employeeUser2 = User::create([
            'name' => 'Bob Engineer',
            'username' => 'bobeng',
            'email' => 'bob@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employeeUser2->id,
            'employee_code' => 'EMP-002',
            'first_name' => 'Bob',
            'last_name' => 'Engineer',
            'email' => 'bob@example.com',
            'gender' => 'male',
            'date_of_birth' => '1993-08-20',
            'joining_date' => '2023-02-15',
            'department' => 'Engineering',
            'designation' => 'DevOps Engineer',
            'shift_id' => $shift->id,
            'status' => \App\Enums\EmployeeStatus::ACTIVE,
        ]);

        $this->clientUser = User::create([
            'name' => 'Client Portal User',
            'username' => 'clientuser',
            'email' => 'client@acme.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Acme Corporation',
            'company_code' => 'CLI-ACME',
            'email' => 'contact@acme.com',
            'status' => \App\Enums\ClientStatus::ACTIVE,
        ]);

        $this->team = Team::create([
            'name' => 'Backend Core Squad',
            'code' => 'SQUAD-BE',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'name' => 'Cloud Platform Architecture',
            'code' => 'PROJ-CPA-01',
            'client_id' => $client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'health' => ProjectHealth::GOOD->value,
        ]);

        $this->milestone = ProjectMilestone::create([
            'project_id' => $this->project->id,
            'title' => 'Phase 1: Core API',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * T227: Task CRUD Lifecycle, Assignment, and Views.
     */
    public function test_t227_task_crud_lifecycle_and_assignment(): void
    {
        // 1. View Index (List View)
        $response = $this->actingAs($this->manager)->get(route('manager.tasks.index'));
        $response->assertOk();
        $response->assertViewIs('manager.tasks.index');

        // 2. View Create Form
        $response = $this->actingAs($this->manager)->get(route('manager.tasks.create', ['project_id' => $this->project->id]));
        $response->assertOk();
        $response->assertViewIs('manager.tasks.create');

        // 3. Store Task
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.store'), [
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
            'title' => 'Design JWT Authentication Middleware',
            'task_code' => 'TSK-AUTH-01',
            'assigned_to' => $this->employeeUser1->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::TODO->value,
            'estimated_hours' => 12.5,
            'due_date' => now()->addDays(7)->toDateString(),
            'description' => 'Implement stateless token authentication with asymmetric keys.',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->project->id,
            'title' => 'Design JWT Authentication Middleware',
            'task_code' => 'TSK-AUTH-01',
            'assigned_to' => $this->employeeUser1->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::TODO->value,
        ]);

        $task = Task::where('task_code', 'TSK-AUTH-01')->first();
        $response->assertRedirect(route('manager.tasks.show', $task));

        // 4. View Show Page
        $response = $this->actingAs($this->manager)->get(route('manager.tasks.show', $task));
        $response->assertOk();
        $response->assertSee('Design JWT Authentication Middleware');
        $response->assertSee('Alice Developer');

        // 5. Edit and Update Task
        $response = $this->actingAs($this->manager)->get(route('manager.tasks.edit', $task));
        $response->assertOk();

        $response = $this->actingAs($this->manager)->put(route('manager.tasks.update', $task), [
            'title' => 'Design OAuth2 & JWT Middleware',
            'task_code' => 'TSK-AUTH-01',
            'assigned_to' => $this->employeeUser1->id,
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'estimated_hours' => 15.0,
            'actual_hours' => 4.0,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $response->assertRedirect(route('manager.tasks.show', $task));

        $task->refresh();
        $this->assertEquals('Design OAuth2 & JWT Middleware', $task->title);
        $this->assertEquals(TaskPriority::URGENT, $task->priority);
        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->status);
        $this->assertEquals(4.0, $task->actual_hours);

        // 6. Soft Delete Task
        $response = $this->actingAs($this->manager)->delete(route('manager.tasks.destroy', $task));
        $response->assertRedirect(route('manager.tasks.index'));
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /**
     * T228: Subtasks, Blocking Dependencies, and Circular-Dependency Prevention.
     */
    public function test_t228_subtasks_dependencies_and_circular_prevention(): void
    {
        $dependencyService = app(TaskDependencyService::class);

        // 1. Create Parent Task and Subtask
        $parentTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Parent Feature: Payment API',
            'task_code' => 'TSK-PAY-01',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::HIGH->value,
        ]);

        $subtask = Task::create([
            'project_id' => $this->project->id,
            'parent_id' => $parentTask->id,
            'title' => 'Subtask: Stripe Webhook Listener',
            'task_code' => 'TSK-PAY-02',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);

        $this->assertEquals(1, $parentTask->subtasks()->count());
        $this->assertEquals($parentTask->id, $subtask->parent->id);

        // 2. Create Dependent Task 3
        $task3 = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Task 3: Production Deployment',
            'task_code' => 'TSK-PAY-03',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::HIGH->value,
        ]);

        // Task 3 depends on Subtask (TSK-PAY-02)
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.dependencies.store', $task3), [
            'depends_on_task_id' => $subtask->id,
            'dependency_type' => 'blocks',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('task_dependencies', [
            'task_id' => $task3->id,
            'depends_on_task_id' => $subtask->id,
            'dependency_type' => 'blocks',
        ]);

        // Subtask depends on ParentTask (TSK-PAY-01)
        $this->actingAs($this->manager)->post(route('manager.tasks.dependencies.store', $subtask), [
            'depends_on_task_id' => $parentTask->id,
            'dependency_type' => 'blocks',
        ]);

        // 3. Circular Dependency Prevention (ParentTask -> Task3 would create cycle: Parent -> Subtask -> Task3 -> Parent)
        $this->assertTrue($dependencyService->createsCycle($parentTask->id, $task3->id));

        $response = $this->actingAs($this->manager)->post(route('manager.tasks.dependencies.store', $parentTask), [
            'depends_on_task_id' => $task3->id,
            'dependency_type' => 'blocks',
        ]);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('task_dependencies', [
            'task_id' => $parentTask->id,
            'depends_on_task_id' => $task3->id,
        ]);

        // 4. Blocker Enforcement: Task 3 cannot be completed while Subtask is uncompleted
        $this->assertTrue($task3->isBlocked());

        $response = $this->actingAs($this->manager)->post(route('manager.tasks.status', $task3), [
            'status' => TaskStatus::DONE->value,
        ]);
        $response->assertSessionHas('error');

        $task3->refresh();
        $this->assertEquals(TaskStatus::BLOCKED, $task3->status); // Auto-flagged as blocked
    }

    /**
     * T229: Recurring Task Rules & Safe Next-Occurrence Generation.
     */
    public function test_t229_recurring_task_rules_and_automation(): void
    {
        $recurringService = app(RecurringTaskService::class);

        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Weekly Security Patch Review',
            'task_code' => 'TSK-REC-01',
            'assigned_to' => $this->employeeUser1->id,
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => now()->toDateString(),
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'created_by' => $this->manager->id,
        ]);

        // Complete the recurring task via status endpoint
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.status', $task), [
            'status' => TaskStatus::DONE->value,
        ]);
        $response->assertRedirect();

        $task->refresh();
        $this->assertEquals(TaskStatus::DONE, $task->status);
        $this->assertNotNull($task->completed_at);

        // Assert next recurring task was automatically generated for +1 week
        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->project->id,
            'title' => 'Weekly Security Patch Review',
            'status' => TaskStatus::TODO->value,
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
    }

    /**
     * T230 & T231: Checklists, Internal Comments, and File Attachments.
     */
    public function test_t230_t231_checklists_comments_and_attachments(): void
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Database Schema Migration',
            'task_code' => 'TSK-DB-01',
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::HIGH->value,
        ]);

        // 1. Checklist Add & Toggle
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.checklists.store', $task), [
            'title' => 'Run database backup before migration',
        ]);
        $response->assertRedirect();

        $checklist = TaskChecklist::where('task_id', $task->id)->first();
        $this->assertNotNull($checklist);
        $this->assertFalse($checklist->is_completed);

        $this->actingAs($this->manager)->post(route('manager.tasks.checklists.toggle', ['task' => $task, 'checklist' => $checklist]));
        $checklist->refresh();
        $this->assertTrue($checklist->is_completed);
        $this->assertEquals(100, $task->checklistProgress());

        // 2. Internal Comment
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.comments.store', $task), [
            'comment' => 'Completed dry-run on staging database without issues.',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $this->manager->id,
            'comment' => 'Completed dry-run on staging database without issues.',
            'is_internal' => true,
        ]);

        // 3. File Attachment Upload & Download
        $fakeFile = UploadedFile::fake()->create('schema_v2.sql', 500, 'application/sql');
        $response = $this->actingAs($this->manager)->post(route('manager.tasks.attachments.store', $task), [
            'file' => $fakeFile,
        ]);
        $response->assertRedirect();

        $attachment = TaskAttachment::where('task_id', $task->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('schema_v2.sql', $attachment->file_name);

        $downloadResponse = $this->actingAs($this->manager)->get(route('manager.tasks.attachments.download', ['task' => $task, 'attachment' => $attachment]));
        $downloadResponse->assertOk();
    }

    /**
     * T232: Task History Audit Trail.
     */
    public function test_t232_task_history_audit_trail(): void
    {
        // 1. Create task records history
        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Audited Task History',
            'task_code' => 'TSK-HIST-01',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
            'created_by' => $this->manager->id,
        ]);

        // 2. Status change logs history
        $this->actingAs($this->manager)->post(route('manager.tasks.status', $task), [
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id,
            'action' => 'task.status_changed',
            'old_value' => 'todo',
            'new_value' => 'in_progress',
        ]);
    }

    /**
     * T233: Kanban Board View.
     */
    public function test_t233_kanban_board_view(): void
    {
        Task::create([
            'project_id' => $this->project->id,
            'title' => 'Todo Task',
            'task_code' => 'TSK-KB-01',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);

        Task::create([
            'project_id' => $this->project->id,
            'title' => 'In Progress Task',
            'task_code' => 'TSK-KB-02',
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::HIGH->value,
        ]);

        $response = $this->actingAs($this->manager)->get(route('manager.tasks.kanban'));
        $response->assertOk();
        $response->assertViewIs('manager.tasks.kanban');
        $response->assertSee('Todo Task');
        $response->assertSee('In Progress Task');
    }

    /**
     * T234: Overdue Task Detection Engine.
     */
    public function test_t234_overdue_task_detection(): void
    {
        $overdueService = app(OverdueTaskDetectionService::class);

        $overdueTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Late Task Deliverable',
            'task_code' => 'TSK-LATE',
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::URGENT->value,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        $onTimeTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'On-time Task Deliverable',
            'task_code' => 'TSK-ONTIME',
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::LOW->value,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($onTimeTask->isOverdue());

        $overdueList = $overdueService->getOverdueTasks();
        $this->assertTrue($overdueList->contains('id', $overdueTask->id));
        $this->assertFalse($overdueList->contains('id', $onTimeTask->id));
    }

    /**
     * T235 & RBAC: Audit Logging & Role Policy Boundaries.
     */
    public function test_t235_and_rbac_boundaries(): void
    {
        // 1. Create task logs audit
        $this->actingAs($this->manager)->post(route('manager.tasks.store'), [
            'project_id' => $this->project->id,
            'title' => 'Audited Project Task',
            'task_code' => 'TSK-AUD-01',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TODO->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Project',
            'target_id' => $this->project->id,
            'action' => 'task.created',
        ]);

        // 2. Team Lead view squad tasks
        $response = $this->actingAs($this->teamLead)->get(route('team-lead.tasks.index'));
        $response->assertOk();

        // 3. HR Admin cannot access Manager Task endpoints
        $this->actingAs($this->hrAdmin)->get(route('manager.tasks.index'))->assertForbidden();

        // 4. Super Admin CAN access Manager Task endpoints
        $this->actingAs($this->superAdmin)->get(route('manager.tasks.index'))->assertOk();
    }
}
