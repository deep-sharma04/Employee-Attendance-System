<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class Phase20ProjectFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected Employee $employeeProfile;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

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
            'password' => 'Password123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::create([
            'name' => 'HR Admin',
            'username' => 'hradmin',
            'email' => 'hradmin@example.com',
            'password' => 'Password123!',
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Project Manager',
            'username' => 'projmanager',
            'email' => 'manager@example.com',
            'password' => 'Password123!',
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Team Lead User',
            'username' => 'teamlead',
            'email' => 'teamlead@example.com',
            'password' => 'Password123!',
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee Contributor',
            'username' => 'employee1',
            'email' => 'employee1@example.com',
            'password' => 'Password123!',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employeeProfile = Employee::create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $shift->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Employee',
            'last_name' => 'Contributor',
            'email' => 'employee1@example.com',
            'joining_date' => now()->subMonths(6)->toDateString(),
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'monthly_salary' => 50000.00,
        ]);

        $this->clientUser = User::create([
            'name' => 'Client Portal User',
            'username' => 'clientuser',
            'email' => 'client@acme.com',
            'password' => 'Password123!',
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);
    }

    /**
     * T199: Test Extended User Roles for Project Module.
     */
    public function test_t199_user_roles_extension_and_helpers(): void
    {
        $this->assertTrue($this->superAdmin->isSuperAdmin());
        $this->assertFalse($this->superAdmin->isManager());

        $this->assertTrue($this->hrAdmin->isHrAdmin());
        $this->assertFalse($this->hrAdmin->isManager());

        $this->assertTrue($this->manager->isManager());
        $this->assertFalse($this->manager->isHrAdmin());
        $this->assertEquals('Manager', $this->manager->role->label());
        $this->assertEquals('manager.dashboard', $this->manager->role->dashboardRoute());

        $this->assertTrue($this->teamLead->isTeamLead());
        $this->assertEquals('Team Lead', $this->teamLead->role->label());
        $this->assertEquals('team-lead.dashboard', $this->teamLead->role->dashboardRoute());

        $this->assertTrue($this->clientUser->isClient());
        $this->assertEquals('Client', $this->clientUser->role->label());
        $this->assertEquals('client-portal.dashboard', $this->clientUser->role->dashboardRoute());

        // Verify seeded roles exist
        $this->assertNotNull(Role::where('slug', 'manager')->first());
        $this->assertNotNull(Role::where('slug', 'team_lead')->first());
        $this->assertNotNull(Role::where('slug', 'client')->first());
    }

    /**
     * T200: Test Clients & Contacts Schema & Relationships.
     */
    public function test_t200_clients_and_contacts_schema(): void
    {
        $client = Client::create([
            'company_name' => 'Acme Corporation',
            'company_code' => 'ACME',
            'email' => 'contact@acme.com',
            'phone' => '+1 555-0199',
            'website' => 'https://acme.example.com',
            'address' => '100 Acme Way, Silicon Valley, CA',
            'status' => ClientStatus::ACTIVE,
            'currency' => 'USD',
            'created_by' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'Acme Corporation',
            'company_code' => 'ACME',
            'status' => 'active',
        ]);

        $primaryContact = ClientContact::create([
            'client_id' => $client->id,
            'name' => 'John Acme',
            'email' => 'john@acme.com',
            'phone' => '+1 555-0100',
            'position' => 'Chief Executive Officer',
            'is_primary' => true,
        ]);

        $secondaryContact = ClientContact::create([
            'client_id' => $client->id,
            'name' => 'Jane Tech',
            'email' => 'jane@acme.com',
            'phone' => '+1 555-0101',
            'position' => 'CTO',
            'is_primary' => false,
        ]);

        $this->assertCount(2, $client->contacts);
        $this->assertEquals($primaryContact->id, $client->primaryContact->id);

        // Link client user
        $clientUserRecord = ClientUser::create([
            'client_id' => $client->id,
            'user_id' => $this->clientUser->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->assertCount(1, $client->clientUsers);
        $this->assertEquals($client->id, $this->clientUser->clientUser->client_id);
    }

    /**
     * T201: Test Teams Migration (1 Manager, 1 Team Lead, Primary Team Membership).
     */
    public function test_t201_teams_and_members_schema(): void
    {
        $team = Team::create([
            'name' => 'Core Platform Engineering',
            'code' => 'ENG-CORE',
            'description' => 'Backend and infrastructure services',
            'department' => 'Engineering',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'code' => 'ENG-CORE',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
        ]);

        $this->assertEquals($this->manager->id, $team->manager->id);
        $this->assertEquals($this->teamLead->id, $team->teamLead->id);

        // Add employee to team
        $membership = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employeeProfile->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->assertCount(1, $team->members);
        $this->assertCount(1, $team->employees);
        $this->assertTrue($this->employeeUser->teams->contains($team));
    }

    /**
     * T202: Test Projects & Members Migrations (No duplicate employee records).
     */
    public function test_t202_projects_and_members_schema(): void
    {
        $client = Client::create([
            'company_name' => 'Apex Global Solutions',
            'company_code' => 'APEX',
            'status' => ClientStatus::ACTIVE,
        ]);

        $team = Team::create([
            'name' => 'Web Apps Team',
            'code' => 'WEB-APPS',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $project = Project::create([
            'name' => 'Customer Portal Redesign',
            'code' => 'PRJ-2026-001',
            'description' => 'Complete overhaul of web customer portal',
            'objectives' => 'Improve responsiveness and user onboarding',
            'scope' => 'Frontend rewrite and API gateway integration',
            'client_id' => $client->id,
            'team_id' => $team->id,
            'manager_id' => $this->manager->id,
            'budget' => 75000.00,
            'estimated_hours' => 600.00,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'code' => 'PRJ-2026-001',
            'status' => 'active',
            'priority' => 'high',
            'health' => 'good',
        ]);

        // Add Project Members
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->manager->id,
            'project_role' => ProjectMemberRole::MANAGER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->teamLead->id,
            'project_role' => ProjectMemberRole::TEAM_LEAD,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employeeProfile->id,
            'project_role' => ProjectMemberRole::MEMBER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertCount(3, $project->members);
        $this->assertCount(1, $project->employees);
        $this->assertEquals($client->id, $project->client->id);
        $this->assertEquals($team->id, $project->team->id);
    }

    /**
     * T203: Test Authorization Policies across all 5 roles.
     */
    public function test_t203_rbac_authorization_policies(): void
    {
        $client = Client::create([
            'company_name' => 'Zenith Enterprises',
            'company_code' => 'ZENITH',
            'status' => ClientStatus::ACTIVE,
        ]);

        ClientUser::create([
            'client_id' => $client->id,
            'user_id' => $this->clientUser->id,
            'is_primary' => true,
        ]);

        $otherClient = Client::create([
            'company_name' => 'Omega Industries',
            'company_code' => 'OMEGA',
            'status' => ClientStatus::ACTIVE,
        ]);

        $team = Team::create([
            'name' => 'Alpha Team',
            'code' => 'ALPHA',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employeeProfile->id,
        ]);

        $project = Project::create([
            'name' => 'Zenith Analytics Dashboard',
            'code' => 'ZEN-001',
            'client_id' => $client->id,
            'team_id' => $team->id,
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employeeProfile->id,
        ]);

        // Client Policy Checks
        $this->assertTrue($this->superAdmin->can('view', $client));
        $this->assertTrue($this->manager->can('view', $client));
        $this->assertTrue($this->clientUser->can('view', $client));
        $this->assertFalse($this->clientUser->can('view', $otherClient)); // Cannot view other client
        $this->assertFalse($this->clientUser->can('update', $client)); // Read-only for client
        $this->assertFalse($this->teamLead->can('view', $client));
        $this->assertFalse($this->employeeUser->can('view', $client));

        // Team Policy Checks
        $this->assertTrue($this->superAdmin->can('view', $team));
        $this->assertTrue($this->manager->can('view', $team));
        $this->assertTrue($this->manager->can('update', $team));
        $this->assertTrue($this->teamLead->can('view', $team));
        $this->assertFalse($this->teamLead->can('update', $team)); // Team lead cannot update team
        $this->assertFalse($this->teamLead->can('manageMembers', $team)); // Team lead cannot manage membership
        $this->assertTrue($this->employeeUser->can('view', $team));
        $this->assertFalse($this->employeeUser->can('update', $team));
        $this->assertFalse($this->clientUser->can('view', $team));

        // Project Policy Checks
        $this->assertTrue($this->superAdmin->can('view', $project));
        $this->assertTrue($this->superAdmin->can('update', $project));
        $this->assertTrue($this->superAdmin->can('viewFinancials', $project));

        $this->assertTrue($this->manager->can('view', $project));
        $this->assertTrue($this->manager->can('update', $project));
        $this->assertTrue($this->manager->can('viewFinancials', $project));

        $this->assertTrue($this->teamLead->can('view', $project));
        $this->assertFalse($this->teamLead->can('update', $project));
        $this->assertFalse($this->teamLead->can('viewFinancials', $project)); // Financials protected

        $this->assertTrue($this->employeeUser->can('view', $project));
        $this->assertFalse($this->employeeUser->can('update', $project));
        $this->assertFalse($this->employeeUser->can('viewFinancials', $project)); // Financials protected

        $this->assertTrue($this->clientUser->can('view', $project));
        $this->assertFalse($this->clientUser->can('update', $project));
        $this->assertFalse($this->clientUser->can('viewFinancials', $project)); // Financials protected
    }

    /**
     * T204: Test Route Groups & Role Middleware Enforcement.
     */
    public function test_t204_route_groups_and_role_middleware(): void
    {
        // 1. Root redirect
        $this->actingAs($this->superAdmin)->get('/')->assertRedirect(route('super-admin.dashboard'));
        $this->actingAs($this->hrAdmin)->get('/')->assertRedirect(route('hr-admin.dashboard'));
        $this->actingAs($this->employeeUser)->get('/')->assertRedirect(route('employee.dashboard'));
        $this->actingAs($this->manager)->get('/')->assertRedirect(route('manager.dashboard'));
        $this->actingAs($this->teamLead)->get('/')->assertRedirect(route('team-lead.dashboard'));
        $this->actingAs($this->clientUser)->get('/')->assertRedirect(route('client-portal.dashboard'));

        // 2. Manager Area
        $this->actingAs($this->manager)->get(route('manager.dashboard'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('manager.dashboard'))->assertOk();
        $this->actingAs($this->teamLead)->get(route('manager.dashboard'))->assertStatus(403);
        $this->actingAs($this->employeeUser)->get(route('manager.dashboard'))->assertStatus(403);
        $this->actingAs($this->clientUser)->get(route('manager.dashboard'))->assertStatus(403);

        // 3. Team Lead Area
        $this->actingAs($this->teamLead)->get(route('team-lead.dashboard'))->assertOk();
        $this->actingAs($this->manager)->get(route('team-lead.dashboard'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('team-lead.dashboard'))->assertOk();
        $this->actingAs($this->employeeUser)->get(route('team-lead.dashboard'))->assertStatus(403);
        $this->actingAs($this->clientUser)->get(route('team-lead.dashboard'))->assertStatus(403);

        // 4. Client Portal Area
        $this->actingAs($this->clientUser)->get(route('client-portal.dashboard'))->assertOk();
        $this->actingAs($this->employeeUser)->get(route('client-portal.dashboard'))->assertStatus(403);
        $this->actingAs($this->teamLead)->get(route('client-portal.dashboard'))->assertStatus(403);
        $this->actingAs($this->manager)->get(route('client-portal.dashboard'))->assertStatus(403);

        // 5. Manager cannot access HR Admin Area (Strict separation of HR vs Project management)
        $this->actingAs($this->manager)->get(route('hr-admin.dashboard'))->assertStatus(403);
    }

    /**
     * T205: Test Project Audit Logging Extension & Immutability.
     */
    public function test_t205_audit_logging_for_projects(): void
    {
        $auditLogger = app(AuditLoggerService::class);

        $client = Client::create([
            'company_name' => 'Delta Systems',
            'company_code' => 'DELTA',
            'status' => ClientStatus::ACTIVE,
        ]);

        $project = Project::create([
            'name' => 'Delta ERP Migration',
            'code' => 'DELTA-ERP',
            'client_id' => $client->id,
            'status' => ProjectStatus::PLANNING,
        ]);

        $this->actingAs($this->manager);

        $auditLogger->logProject(
            action: 'project.status_updated',
            projectId: $project->id,
            beforeValues: ['status' => 'planning'],
            afterValues: ['status' => 'active'],
            description: 'Project status transitioned from Planning to Active by Manager'
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'actor_role' => 'manager',
            'action' => 'project.status_updated',
            'target_type' => 'Project',
            'target_id' => $project->id,
        ]);

        $log = AuditLog::where('action', 'project.status_updated')->first();
        $this->assertNotNull($log);
        $this->assertEquals('Project', $log->target_type);

        // Verify immutable property
        $this->expectException(\RuntimeException::class);
        $log->update(['description' => 'Tampered Description']);
    }

    /**
     * T206: Test Referential Integrity, Unique Constraints & Soft Deletes.
     */
    public function test_t206_referential_integrity_and_unique_constraints(): void
    {
        $client = Client::create([
            'company_name' => 'SecureCorp',
            'company_code' => 'SECURE',
            'status' => ClientStatus::ACTIVE,
        ]);

        $contact = ClientContact::create([
            'client_id' => $client->id,
            'name' => 'Security Lead',
            'email' => 'security@securecorp.com',
        ]);

        $team = Team::create([
            'name' => 'Security Ops Team',
            'code' => 'SEC-OPS',
            'is_active' => true,
        ]);

        // Duplicate team member should violate unique constraint
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employeeProfile->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->employeeUser->id,
        ]);
    }
}
