<?php

namespace Tests\Feature;

use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase22TeamsAndProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser1;
    protected User $employeeUser2;
    protected Employee $employee1;
    protected Employee $employee2;
    protected User $hrAdmin;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

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
            'name' => 'Engineering Manager',
            'username' => 'engmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Lead Developer',
            'username' => 'leaddev',
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

        $this->employeeUser1 = User::create([
            'name' => 'Alice Developer',
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee1 = Employee::create([
            'user_id' => $this->employeeUser1->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Alice',
            'last_name' => 'Developer',
            'email' => 'alice@example.com',
            'phone' => '111-222-3333',
            'gender' => 'female',
            'date_of_birth' => '1995-05-15',
            'joining_date' => '2023-01-10',
            'department' => 'Engineering',
            'designation' => 'Senior Backend Developer',
            'shift_id' => $this->shift->id,
            'status' => \App\Enums\EmployeeStatus::ACTIVE,
        ]);

        $this->employeeUser2 = User::create([
            'name' => 'Bob Engineer',
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee2 = Employee::create([
            'user_id' => $this->employeeUser2->id,
            'employee_code' => 'EMP-002',
            'first_name' => 'Bob',
            'last_name' => 'Engineer',
            'email' => 'bob@example.com',
            'phone' => '444-555-6666',
            'gender' => 'male',
            'date_of_birth' => '1992-08-20',
            'joining_date' => '2023-03-01',
            'department' => 'Engineering',
            'designation' => 'DevOps Engineer',
            'shift_id' => $this->shift->id,
            'status' => \App\Enums\EmployeeStatus::ACTIVE,
        ]);
    }

    /**
     * T214: Team Management CRUD operations and 1 Manager + 1 Team Lead assignment.
     */
    public function test_t214_team_crud_and_leadership_assignment(): void
    {
        // 1. View Index
        $response = $this->actingAs($this->manager)->get(route('manager.teams.index'));
        $response->assertOk();
        $response->assertViewIs('manager.teams.index');

        // 2. View Create
        $response = $this->actingAs($this->manager)->get(route('manager.teams.create'));
        $response->assertOk();
        $response->assertViewIs('manager.teams.create');

        // 3. Store Team
        $response = $this->actingAs($this->manager)->post(route('manager.teams.store'), [
            'name' => 'Backend Core Squad',
            'code' => 'TEAM-BE-01',
            'department' => 'Engineering',
            'description' => 'Handles API services and database architecture.',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Backend Core Squad',
            'code' => 'TEAM-BE-01',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $team = Team::where('code', 'TEAM-BE-01')->first();
        $response->assertRedirect(route('manager.teams.show', $team));

        // 4. View Show
        $response = $this->actingAs($this->manager)->get(route('manager.teams.show', $team));
        $response->assertOk();
        $response->assertSee('Backend Core Squad');
        $response->assertSee($this->manager->name);
        $response->assertSee($this->teamLead->name);

        // 5. View Edit
        $response = $this->actingAs($this->manager)->get(route('manager.teams.edit', $team));
        $response->assertOk();

        // 6. Update Team
        $response = $this->actingAs($this->manager)->put(route('manager.teams.update', $team), [
            'name' => 'Backend Platform Team',
            'code' => 'TEAM-BE-01',
            'department' => 'Infrastructure',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => 1,
        ]);
        $response->assertRedirect(route('manager.teams.show', $team));

        $team->refresh();
        $this->assertEquals('Backend Platform Team', $team->name);
        $this->assertEquals('Infrastructure', $team->department);

        // 7. Soft Delete Team
        $response = $this->actingAs($this->manager)->delete(route('manager.teams.destroy', $team));
        $response->assertRedirect(route('manager.teams.index'));
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    /**
     * T215: Team Membership & Primary Team Enforcement.
     */
    public function test_t215_team_membership_and_primary_team_rule(): void
    {
        $team1 = Team::create([
            'name' => 'Frontend Squad',
            'code' => 'TEAM-FE',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $team2 = Team::create([
            'name' => 'Mobile Squad',
            'code' => 'TEAM-MOB',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        // 1. Add Employee 1 to Team 1 (defaults to is_primary = true)
        $response = $this->actingAs($this->manager)->post(route('manager.teams.members.add', $team1), [
            'employee_id' => $this->employee1->id,
            'is_primary' => 1,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team1->id,
            'user_id' => $this->employeeUser1->id,
            'employee_id' => $this->employee1->id,
            'is_primary' => true,
        ]);

        $memberTeam1 = TeamMember::where('team_id', $team1->id)->where('employee_id', $this->employee1->id)->first();

        // 2. Add Employee 1 to Team 2 with is_primary = true -> Team 1 membership should become is_primary = false
        $response = $this->actingAs($this->manager)->post(route('manager.teams.members.add', $team2), [
            'employee_id' => $this->employee1->id,
            'is_primary' => 1,
        ]);
        $response->assertRedirect();

        $memberTeam2 = TeamMember::where('team_id', $team2->id)->where('employee_id', $this->employee1->id)->first();
        $this->assertTrue((bool) $memberTeam2->is_primary);

        $memberTeam1->refresh();
        $this->assertFalse((bool) $memberTeam1->is_primary);

        // 3. Set Primary back to Team 1 via primary endpoint
        $response = $this->actingAs($this->manager)->post(route('manager.teams.members.primary', [
            'team' => $team1,
            'member' => $memberTeam1,
        ]));
        $response->assertRedirect();

        $memberTeam1->refresh();
        $memberTeam2->refresh();
        $this->assertTrue((bool) $memberTeam1->is_primary);
        $this->assertFalse((bool) $memberTeam2->is_primary);

        // 4. Remove Member from Team 2
        $response = $this->actingAs($this->manager)->delete(route('manager.teams.members.remove', [
            'team' => $team2,
            'member' => $memberTeam2,
        ]));
        $response->assertRedirect();

        $this->assertDatabaseMissing('team_members', ['id' => $memberTeam2->id]);
    }

    /**
     * T216: Team Leadership Scope Enforcement.
     */
    public function test_t216_team_leadership_scope_enforcement(): void
    {
        $myTeam = Team::create([
            'name' => 'Led by Team Lead',
            'code' => 'TEAM-LED',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $otherTeam = Team::create([
            'name' => 'Other Team',
            'code' => 'TEAM-OTHER',
            'manager_id' => $this->manager->id,
            'team_lead_id' => null,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $myTeam->id,
            'user_id' => $this->employeeUser1->id,
            'employee_id' => $this->employee1->id,
            'is_primary' => true,
        ]);

        // 1. Team Lead can view assigned team workspace
        $response = $this->actingAs($this->teamLead)->get(route('team-lead.team.index'));
        $response->assertOk();
        $response->assertSee('Led by Team Lead');

        // 2. Team Lead can view member skills profile
        $response = $this->actingAs($this->teamLead)->get(route('team-lead.team.members.show', $this->employee1));
        $response->assertOk();
        $response->assertSee('Alice');

        // 3. Team Lead CANNOT create, edit or delete teams
        $this->actingAs($this->teamLead)->get(route('manager.teams.create'))->assertForbidden();
        $this->actingAs($this->teamLead)->post(route('manager.teams.store'), ['name' => 'Unauthorized Team'])->assertForbidden();
        $this->actingAs($this->teamLead)->delete(route('manager.teams.destroy', $myTeam))->assertForbidden();

        // 4. Team Lead CANNOT add or remove members directly from manager endpoints
        $this->actingAs($this->teamLead)->post(route('manager.teams.members.add', $myTeam), [
            'employee_id' => $this->employee2->id,
        ])->assertForbidden();
    }

    /**
     * T217: Extend Employee Profile for Projects without altering core HR data.
     */
    public function test_t217_extend_employee_project_profile(): void
    {
        // 1. Core Employee record has standard fields
        $this->assertEquals('EMP-001', $this->employee1->employee_code);

        // 2. Update/Create project profile extension
        $response = $this->actingAs($this->manager)->put(route('manager.employees.profiles.update', $this->employee1), [
            'skills' => 'PHP, Laravel, Vue.js, PostgreSQL, Docker',
            'availability_status' => 'partially_available',
            'weekly_capacity_hours' => 35,
            'experience_years' => 4.5,
            'bio' => 'Senior backend developer specializing in high-throughput systems.',
            'timezone' => 'America/New_York',
        ]);
        $response->assertRedirect(route('manager.employees.profiles.show', $this->employee1));

        $this->assertDatabaseHas('employee_project_profiles', [
            'employee_id' => $this->employee1->id,
            'user_id' => $this->employeeUser1->id,
            'availability_status' => 'partially_available',
            'weekly_capacity_hours' => 35,
            'timezone' => 'America/New_York',
        ]);

        $this->employee1->refresh();
        $profile = $this->employee1->projectProfile;
        $this->assertNotNull($profile);
        $this->assertContains('Laravel', $profile->skills);
        $this->assertContains('Docker', $profile->skills);
        $this->assertEquals('Partially Available', $profile->availabilityLabel());

        // 3. Verify core employee record is completely preserved without corruption
        $this->assertEquals('EMP-001', $this->employee1->employee_code);
        $this->assertEquals('Alice', $this->employee1->first_name);
        $this->assertEquals('1995-05-15', $this->employee1->date_of_birth->format('Y-m-d'));
    }

    /**
     * T218: View Project Employee Profile (masks sensitive HR data).
     */
    public function test_t218_view_project_employee_profile_masking_sensitive_hr(): void
    {
        EmployeeProjectProfile::create([
            'employee_id' => $this->employee1->id,
            'user_id' => $this->employeeUser1->id,
            'skills' => ['React', 'Next.js', 'TailwindCSS'],
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
            'experience_years' => 3.0,
            'bio' => 'Frontend architect.',
        ]);

        $response = $this->actingAs($this->manager)->get(route('manager.employees.profiles.show', $this->employee1));
        $response->assertOk();
        $response->assertSee('Alice Developer');
        $response->assertSee('React');
        $response->assertSee('Next.js');
        $response->assertSee('40 hrs/week');

        // Sensitive HR data must NOT be present on project profile view
        $response->assertDontSee('Basic Salary');
        $response->assertDontSee('Bank Account');
        $response->assertDontSee('Payroll Slip');
    }

    /**
     * T219: Audit Logging for Team & Profile changes.
     */
    public function test_t219_audit_logging_for_team_operations(): void
    {
        // 1. Create Team generates audit log
        $this->actingAs($this->manager)->post(route('manager.teams.store'), [
            'name' => 'Data Science Squad',
            'code' => 'TEAM-DS',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => 1,
        ]);

        $team = Team::where('code', 'TEAM-DS')->first();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Team',
            'target_id' => $team->id,
            'action' => 'team.created',
        ]);

        // 2. Add Member generates audit log
        $this->actingAs($this->manager)->post(route('manager.teams.members.add', $team), [
            'employee_id' => $this->employee1->id,
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Team',
            'target_id' => $team->id,
            'action' => 'team_member.added',
        ]);

        // 3. Update Employee Project Profile generates audit log
        $this->actingAs($this->manager)->put(route('manager.employees.profiles.update', $this->employee1), [
            'skills' => 'Python, PyTorch',
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Employee',
            'target_id' => $this->employee1->id,
            'action' => 'employee_project_profile.updated',
        ]);
    }

    /**
     * RBAC Restrictions on Teams.
     */
    public function test_rbac_restrictions_on_teams(): void
    {
        $team = Team::create([
            'name' => 'Security Squad',
            'code' => 'TEAM-SEC',
            'manager_id' => $this->manager->id,
            'is_active' => true,
        ]);

        // Employee cannot view manager team index or mutate
        $this->actingAs($this->employeeUser1)->get(route('manager.teams.index'))->assertForbidden();
        $this->actingAs($this->employeeUser1)->post(route('manager.teams.store'), ['name' => 'Unauthorized'])->assertForbidden();

        // HR Admin cannot access project team management
        $this->actingAs($this->hrAdmin)->get(route('manager.teams.index'))->assertForbidden();

        // Super Admin CAN manage teams
        $this->actingAs($this->superAdmin)->get(route('manager.teams.index'))->assertOk();
    }
}
