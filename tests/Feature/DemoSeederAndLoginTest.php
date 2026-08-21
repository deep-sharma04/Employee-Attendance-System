<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\Timesheet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederAndLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed full database including DemoDataSeeder
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test login page renders with role selector and demo presets.
     */
    public function test_login_page_renders_with_role_selector(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('Select Your Role');
        $response->assertSee('Admin');
        $response->assertSee('HR Admin');
        $response->assertSee('Manager');
        $response->assertSee('Team Lead');
        $response->assertSee('Employee');
        $response->assertSee('Client');
        $response->assertSee('Remember me');
        $response->assertSee('Sign In');
    }

    public function test_superadmin_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'super_admin',
            'username' => 'superadmin',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::SUPER_ADMIN, auth()->user()->role);
    }

    public function test_admin_alias_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'super_admin',
            'username' => 'admin',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_hradmin_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'hr_admin',
            'username' => 'hradmin',
            'password' => 'HrAdmin@12345',
        ]);

        $response->assertRedirect(route('hr-admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::HR_ADMIN, auth()->user()->role);
    }

    public function test_manager_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'manager',
            'username' => 'manager',
            'password' => 'Manager@12345',
        ]);

        $response->assertRedirect(route('manager.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::MANAGER, auth()->user()->role);
    }

    public function test_team_lead_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'team_lead',
            'username' => 'teamlead',
            'password' => 'TeamLead@12345',
        ]);

        $response->assertRedirect(route('team-lead.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::TEAM_LEAD, auth()->user()->role);
    }

    public function test_employee_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => 'employee',
            'password' => 'Employee@12345',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::EMPLOYEE, auth()->user()->role);
    }

    public function test_client_demo_login(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'client',
            'username' => 'client',
            'password' => 'Client@12345',
        ]);

        $response->assertRedirect(route('client-portal.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals(UserRole::CLIENT, auth()->user()->role);
    }

    /**
     * Test that wrong role is rejected even if credentials are correct.
     */
    public function test_wrong_role_is_rejected(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => 'superadmin',
            'password' => 'Admin@12345',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    /**
     * Test that missing role is rejected.
     */
    public function test_missing_role_is_rejected(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'superadmin',
            'password' => 'Admin@12345',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    /**
     * Test remember me checkbox works.
     */
    public function test_remember_me_works(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => 'employee',
            'password' => 'Employee@12345',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticated();

        // Check remember token is set
        $user = User::where('username', 'employee')->first();
        $this->assertNotNull($user->fresh()->remember_token);
    }

    /**
     * Verify all demo entities were properly created and connected by the seeder.
     */
    public function test_demo_seeder_entities_created_successfully(): void
    {
        // Users & Employees
        $this->assertDatabaseHas('users', ['username' => 'superadmin']);
        $this->assertDatabaseHas('users', ['username' => 'hradmin']);
        $this->assertDatabaseHas('users', ['username' => 'manager']);
        $this->assertDatabaseHas('users', ['username' => 'teamlead']);
        $this->assertDatabaseHas('users', ['username' => 'employee']);
        $this->assertDatabaseHas('users', ['username' => 'client']);

        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-MGR-001']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-TL-001']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-DEV-001']);

        // Client & Contacts
        $this->assertDatabaseHas('clients', ['company_code' => 'CLI-APEX']);
        $this->assertDatabaseHas('client_contacts', ['email' => 'rsterling@apex.com']);

        // Teams & Squads
        $this->assertDatabaseHas('teams', ['code' => 'SQUAD-CORE']);

        // Projects & Milestones
        $this->assertDatabaseHas('projects', ['code' => 'PROJ-BANK-01']);
        $this->assertDatabaseHas('project_milestones', ['title' => 'Phase 1: High-Level Architecture & API Design']);

        // Tasks & Timesheets
        $this->assertDatabaseHas('tasks', ['task_code' => 'TSK-BANK-01']);
        $this->assertDatabaseHas('timesheets', ['status' => 'approved']);
        $this->assertDatabaseHas('timesheets', ['status' => 'submitted']);
    }
}
