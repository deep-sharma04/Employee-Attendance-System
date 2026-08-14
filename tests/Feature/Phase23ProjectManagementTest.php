<?php

namespace Tests\Feature;

use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use App\Services\Project\ProjectHealthService;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase23ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected User $clientUser;
    protected User $hrAdmin;
    protected Client $client;
    protected Team $team;
    protected Employee $employee;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $this->shift = Shift::create([
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
            'name' => 'Project Manager',
            'username' => 'projmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Tech Lead',
            'username' => 'techlead',
            'email' => 'lead@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::create([
            'name' => 'HR Administrator',
            'username' => 'hradmin',
            'email' => 'hr@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Senior Developer',
            'username' => 'developer1',
            'email' => 'dev@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Senior',
            'last_name' => 'Developer',
            'email' => 'dev@example.com',
            'phone' => '111-222-3333',
            'gender' => 'female',
            'date_of_birth' => '1994-06-12',
            'joining_date' => '2023-01-10',
            'department' => 'Engineering',
            'designation' => 'Senior Backend Developer',
            'shift_id' => $this->shift->id,
            'status' => \App\Enums\EmployeeStatus::ACTIVE,
        ]);

        $this->client = Client::create([
            'company_name' => 'Acme Corporation',
            'company_code' => 'CLI-ACME',
            'email' => 'contact@acme.com',
            'status' => \App\Enums\ClientStatus::ACTIVE,
        ]);

        $this->clientUser = User::create([
            'name' => 'Client Portal Viewer',
            'username' => 'acmeclient',
            'email' => 'portal@acme.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->team = Team::create([
            'name' => 'Core Engineering Squad',
            'code' => 'SQUAD-CORE',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);
    }

    /**
     * T220: Project CRUD operations with all metadata fields and relationship links.
     */
    public function test_t220_project_crud_lifecycle(): void
    {
        // 1. Index
        $response = $this->actingAs($this->manager)->get(route('manager.projects.index'));
        $response->assertOk();
        $response->assertViewIs('manager.projects.index');

        // 2. Create View
        $response = $this->actingAs($this->manager)->get(route('manager.projects.create'));
        $response->assertOk();
        $response->assertViewIs('manager.projects.create');

        // 3. Store Project
        $response = $this->actingAs($this->manager)->post(route('manager.projects.store'), [
            'name' => 'Acme Cloud Migration Platform',
            'code' => 'PROJ-ACM-01',
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 75000.00,
            'estimated_hours' => 500,
            'start_date' => now()->subDays(5)->toDateString(),
            'deadline' => now()->addDays(25)->toDateString(),
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'description' => 'Migrate infrastructure to cloud native Kubernetes.',
            'objectives' => 'High availability and zero-downtime cutover.',
            'scope' => 'Database, API, frontends.',
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Acme Cloud Migration Platform',
            'code' => 'PROJ-ACM-01',
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 75000.00,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
        ]);

        $project = Project::where('code', 'PROJ-ACM-01')->first();
        $response->assertRedirect(route('manager.projects.show', $project));

        // 4. Show View
        $response = $this->actingAs($this->manager)->get(route('manager.projects.show', $project));
        $response->assertOk();
        $response->assertSee('Acme Cloud Migration Platform');
        $response->assertSee('Acme Corporation');
        $response->assertSee('Core Engineering Squad');

        // 5. Edit View
        $response = $this->actingAs($this->manager)->get(route('manager.projects.edit', $project));
        $response->assertOk();

        // 6. Update Project
        $response = $this->actingAs($this->manager)->put(route('manager.projects.update', $project), [
            'name' => 'Acme Cloud & DevOps Migration',
            'code' => 'PROJ-ACM-01',
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 85000.00,
            'estimated_hours' => 600,
            'start_date' => now()->subDays(5)->toDateString(),
            'deadline' => now()->addDays(30)->toDateString(),
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::URGENT->value,
        ]);
        $response->assertRedirect(route('manager.projects.show', $project));

        $project->refresh();
        $this->assertEquals('Acme Cloud & DevOps Migration', $project->name);
        $this->assertEquals(85000.00, $project->budget);
        $this->assertEquals(ProjectPriority::URGENT, $project->priority);

        // 7. Soft Delete
        $response = $this->actingAs($this->manager)->delete(route('manager.projects.destroy', $project));
        $response->assertRedirect(route('manager.projects.index'));
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /**
     * T221: Manage Project Milestones (Create, Update, Reorder, Complete, Delete).
     */
    public function test_t221_project_milestones_management(): void
    {
        $project = Project::create([
            'name' => 'Fintech Payment Gateway',
            'code' => 'PROJ-PAY-01',
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'health' => ProjectHealth::GOOD->value,
        ]);

        // 1. Create Milestone 1
        $response = $this->actingAs($this->manager)->post(route('manager.projects.milestones.store', $project), [
            'title' => 'API Specification & Schemas',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'pending',
            'order' => 1,
            'description' => 'Swagger/OpenAPI contract approval',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'title' => 'API Specification & Schemas',
            'status' => 'pending',
            'order' => 1,
        ]);

        $milestone1 = ProjectMilestone::where('project_id', $project->id)->where('title', 'API Specification & Schemas')->first();

        // 2. Create Milestone 2
        $this->actingAs($this->manager)->post(route('manager.projects.milestones.store', $project), [
            'title' => 'Core Ledger Integration',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'in_progress',
            'order' => 2,
        ]);

        $milestone2 = ProjectMilestone::where('project_id', $project->id)->where('title', 'Core Ledger Integration')->first();
        $this->assertEquals(2, $project->milestones()->count());

        // 3. Toggle Completion Status on Milestone 1
        $response = $this->actingAs($this->manager)->post(route('manager.projects.milestones.toggle', [
            'project' => $project,
            'milestone' => $milestone1,
        ]));
        $response->assertRedirect();

        $milestone1->refresh();
        $this->assertEquals('completed', $milestone1->status);
        $this->assertNotNull($milestone1->completed_at);
        $this->assertEquals(50, $project->progressPercentage()); // 1 of 2 completed = 50%

        // 4. Update Milestone 2
        $response = $this->actingAs($this->manager)->put(route('manager.projects.milestones.update', [
            'project' => $project,
            'milestone' => $milestone2,
        ]), [
            'title' => 'Core Banking & Ledger Integration',
            'due_date' => now()->addDays(25)->toDateString(),
            'status' => 'completed',
            'order' => 2,
        ]);
        $response->assertRedirect();

        $milestone2->refresh();
        $this->assertEquals('Core Banking & Ledger Integration', $milestone2->title);
        $this->assertEquals('completed', $milestone2->status);
        $this->assertEquals(100, $project->progressPercentage()); // 2 of 2 completed = 100%

        // 5. Delete Milestone
        $response = $this->actingAs($this->manager)->delete(route('manager.projects.milestones.destroy', [
            'project' => $project,
            'milestone' => $milestone2,
        ]));
        $response->assertRedirect();
        $this->assertSoftDeleted('project_milestones', ['id' => $milestone2->id]);
    }

    /**
     * T222: Project Status & Priority Transitions.
     */
    public function test_t222_project_status_and_priority_transitions(): void
    {
        $project = Project::create([
            'name' => 'Workflow Automation',
            'code' => 'PROJ-AUTO',
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::PLANNING->value,
            'priority' => ProjectPriority::LOW->value,
            'health' => ProjectHealth::NOT_STARTED->value,
        ]);

        // Transition from PLANNING to ACTIVE with Priority HIGH
        $response = $this->actingAs($this->manager)->post(route('manager.projects.status', $project), [
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
        ]);
        $response->assertRedirect();

        $project->refresh();
        $this->assertEquals(ProjectStatus::ACTIVE, $project->status);
        $this->assertEquals(ProjectPriority::HIGH, $project->priority);

        // Transition to ON_HOLD
        $this->actingAs($this->manager)->post(route('manager.projects.status', $project), [
            'status' => ProjectStatus::ON_HOLD->value,
        ]);
        $project->refresh();
        $this->assertEquals(ProjectStatus::ON_HOLD, $project->status);

        // Transition to COMPLETED (sets end_date)
        $this->actingAs($this->manager)->post(route('manager.projects.status', $project), [
            'status' => ProjectStatus::COMPLETED->value,
        ]);
        $project->refresh();
        $this->assertEquals(ProjectStatus::COMPLETED, $project->status);
        $this->assertNotNull($project->end_date);
    }

    /**
     * T223: Deterministic Project Health Engine.
     */
    public function test_t223_deterministic_project_health_calculation(): void
    {
        $healthService = app(ProjectHealthService::class);

        // Case 1: Completed project is always GOOD
        $completedProject = Project::create([
            'name' => 'Completed Project',
            'code' => 'PROJ-CMP',
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::COMPLETED->value,
            'health' => ProjectHealth::GOOD->value,
        ]);
        $this->assertEquals(ProjectHealth::GOOD, $healthService->calculateHealth($completedProject));

        // Case 2: Active project on schedule is GOOD
        $onTrackProject = Project::create([
            'name' => 'On Track Project',
            'code' => 'PROJ-TRK',
            'manager_id' => $this->manager->id,
            'start_date' => now()->subDays(10),
            'deadline' => now()->addDays(90), // ~10% elapsed
            'status' => ProjectStatus::ACTIVE->value,
            'health' => ProjectHealth::GOOD->value,
        ]);
        ProjectMilestone::create([
            'project_id' => $onTrackProject->id,
            'title' => 'M1',
            'due_date' => now()->addDays(15),
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        ProjectMilestone::create([
            'project_id' => $onTrackProject->id,
            'title' => 'M2',
            'due_date' => now()->addDays(40),
            'status' => 'pending',
        ]);
        // 50% completed vs 10% expected -> schedule variance = 0
        $this->assertEquals(ProjectHealth::GOOD, $healthService->calculateHealth($onTrackProject));

        // Case 3: Overdue milestone triggers AT_RISK or CRITICAL
        $overdueProject = Project::create([
            'name' => 'Overdue Milestone Project',
            'code' => 'PROJ-OVD',
            'manager_id' => $this->manager->id,
            'start_date' => now()->subDays(20),
            'deadline' => now()->addDays(40),
            'status' => ProjectStatus::ACTIVE->value,
            'health' => ProjectHealth::GOOD->value,
        ]);
        ProjectMilestone::create([
            'project_id' => $overdueProject->id,
            'title' => 'Late Milestone 1',
            'due_date' => now()->subDays(5), // Overdue!
            'status' => 'pending',
        ]);
        ProjectMilestone::create([
            'project_id' => $overdueProject->id,
            'title' => 'Late Milestone 2',
            'due_date' => now()->subDays(2), // Overdue!
            'status' => 'pending',
        ]);
        // 2 overdue milestones meets default critical threshold (>= 2)
        $this->assertEquals(ProjectHealth::CRITICAL, $healthService->calculateHealth($overdueProject));

        // Case 4: Past project deadline and not completed is CRITICAL
        $pastDeadlineProject = Project::create([
            'name' => 'Past Deadline Project',
            'code' => 'PROJ-PST',
            'manager_id' => $this->manager->id,
            'start_date' => now()->subDays(30),
            'deadline' => now()->subDays(2), // Deadline passed
            'status' => ProjectStatus::ACTIVE->value,
            'health' => ProjectHealth::GOOD->value,
        ]);
        $this->assertEquals(ProjectHealth::CRITICAL, $healthService->calculateHealth($pastDeadlineProject));
    }

    /**
     * T224: Project Health Thresholds Configuration without code change.
     */
    public function test_t224_project_health_configuration(): void
    {
        // 1. Super Admin views health config page
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.settings.project-health'));
        $response->assertOk();
        $response->assertViewIs('super-admin.settings.project-health');

        // 2. Update health thresholds dynamically
        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.settings.project-health.update'), [
            'project_health_schedule_variance_at_risk' => 20,
            'project_health_schedule_variance_critical' => 45,
            'project_health_overdue_milestones_at_risk' => 2,
            'project_health_overdue_milestones_critical' => 4,
        ]);
        $response->assertRedirect(route('super-admin.settings.project-health'));

        $this->assertDatabaseHas('company_settings', [
            'key' => 'project_health_schedule_variance_at_risk',
            'value' => '20',
        ]);
        $this->assertDatabaseHas('company_settings', [
            'key' => 'project_health_schedule_variance_critical',
            'value' => '45',
        ]);
        $this->assertDatabaseHas('company_settings', [
            'key' => 'project_health_overdue_milestones_at_risk',
            'value' => '2',
        ]);
        $this->assertDatabaseHas('company_settings', [
            'key' => 'project_health_overdue_milestones_critical',
            'value' => '4',
        ]);

        // 3. Manager/Employee cannot update system health thresholds
        $this->actingAs($this->manager)->get(route('super-admin.settings.project-health'))->assertForbidden();
        $this->actingAs($this->employeeUser)->post(route('super-admin.settings.project-health.update'), [
            'project_health_schedule_variance_at_risk' => 10,
        ])->assertForbidden();
    }

    /**
     * T225: Project Audit Trail Logging.
     */
    public function test_t225_project_audit_logging(): void
    {
        // 1. Create Project
        $this->actingAs($this->manager)->post(route('manager.projects.store'), [
            'name' => 'Audited Project',
            'code' => 'PROJ-AUD',
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::MEDIUM->value,
        ]);

        $project = Project::where('code', 'PROJ-AUD')->first();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Project',
            'target_id' => $project->id,
            'action' => 'project.created',
        ]);

        // 2. Add Milestone
        $this->actingAs($this->manager)->post(route('manager.projects.milestones.store', $project), [
            'title' => 'Audited Milestone',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Project',
            'target_id' => $project->id,
            'action' => 'project_milestone.created',
        ]);

        // 3. Add Project Member
        $this->actingAs($this->manager)->post(route('manager.projects.members.add', $project), [
            'user_id' => $this->employeeUser->id,
            'project_role' => ProjectMemberRole::MEMBER->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Project',
            'target_id' => $project->id,
            'action' => 'project_member.added',
        ]);
    }

    /**
     * RBAC Restrictions on Projects.
     */
    public function test_rbac_restrictions_on_projects(): void
    {
        $project = Project::create([
            'name' => 'Restricted Project',
            'code' => 'PROJ-RST',
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'health' => ProjectHealth::GOOD->value,
        ]);

        // Employee cannot view manager project index or create
        $this->actingAs($this->employeeUser)->get(route('manager.projects.index'))->assertForbidden();
        $this->actingAs($this->employeeUser)->post(route('manager.projects.store'), ['name' => 'Hack'])->assertForbidden();

        // HR Admin cannot access project management
        $this->actingAs($this->hrAdmin)->get(route('manager.projects.index'))->assertForbidden();

        // Super Admin CAN manage projects
        $this->actingAs($this->superAdmin)->get(route('manager.projects.index'))->assertOk();
    }
}
