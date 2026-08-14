<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2AuthenticationAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_log_in_and_redirects_to_super_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'role' => 'super_admin',
            'username' => 'superadmin',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect('/super-admin/dashboard');
        $this->assertAuthenticated();

        $user = User::where('username', 'superadmin')->first();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('127.0.0.1', $user->last_login_ip);
    }

    public function test_hr_admin_can_log_in_and_redirects_to_hr_dashboard(): void
    {
        $response = $this->post('/login', [
            'role' => 'hr_admin',
            'username' => 'hradmin',
            'password' => 'HrAdmin@12345',
        ]);

        $response->assertRedirect('/hr-admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_employee_can_log_in_and_redirects_to_employee_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'alice.smith',
            'password' => Hash::make('Employee@12345'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP1001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice.smith@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $response = $this->post('/login', [
            'role' => 'employee',
            'username' => 'alice.smith',
            'password' => 'Employee@12345',
        ]);

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_destroys_session_and_redirects_to_login(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_failure_returns_generic_error_for_wrong_password(): void
    {
        $response = $this->post('/login', [
            'role' => 'super_admin',
            'username' => 'superadmin',
            'password' => 'WrongPassword!123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_login_failure_returns_generic_error_for_non_existent_user(): void
    {
        $response = $this->post('/login', [
            'role' => 'employee',
            'username' => 'ghost_user',
            'password' => 'AnyPassword!123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_login_failure_returns_generic_error_for_inactive_user(): void
    {
        User::factory()->create([
            'username' => 'disabled.user',
            'password' => Hash::make('SecretPass@123'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'role' => 'employee',
            'username' => 'disabled.user',
            'password' => 'SecretPass@123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_login_failure_returns_generic_error_for_inactive_employee_status(): void
    {
        $user = User::factory()->create([
            'username' => 'terminated.emp',
            'password' => Hash::make('SecretPass@123'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP9988',
            'first_name' => 'Term',
            'last_name' => 'Inated',
            'email' => 'term@hrm.local',
            'status' => EmployeeStatus::TERMINATED,
        ]);

        $response = $this->post('/login', [
            'role' => 'employee',
            'username' => 'terminated.emp',
            'password' => 'SecretPass@123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_rate_limiting_blocks_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'role' => 'employee',
                'username' => 'brute.force.user',
                'password' => 'WrongPass',
            ]);
        }

        $response = $this->post('/login', [
            'role' => 'employee',
            'username' => 'brute.force.user',
            'password' => 'WrongPass',
        ]);

        $response->assertSessionHasErrors(['username']);
        $errorMessage = session('errors')->first('username');
        $this->assertStringContainsString('Too many login attempts', $errorMessage);
    }

    public function test_passwords_are_stored_using_bcrypt_hashing(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $this->assertTrue(Hash::isHashed($user->password));
        $this->assertTrue(Hash::check('Admin@12345', $user->password));
        $this->assertFalse(str_contains($user->password, 'Admin@12345'));
    }

    public function test_authenticated_user_can_change_password_with_current_password_verification(): void
    {
        $user = User::factory()->create([
            'username' => 'password.changer',
            'password' => Hash::make('OldPassword@123'),
        ]);

        // Wrong current password
        $failResponse = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'InvalidOldPassword',
            'password' => 'NewSecurePassword@123',
            'password_confirmation' => 'NewSecurePassword@123',
        ]);
        $failResponse->assertSessionHasErrors(['current_password']);

        // Correct current password
        $successResponse = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'OldPassword@123',
            'password' => 'NewSecurePassword@123',
            'password_confirmation' => 'NewSecurePassword@123',
        ]);

        $successResponse->assertSessionHas('success');
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword@123', $user->password));
    }

    public function test_forgot_and_reset_password_flow_with_secure_tokens(): void
    {
        $user = User::where('username', 'superadmin')->first();

        // 1. Request forgot password
        $forgotResponse = $this->post('/password/forgot', [
            'username' => 'superadmin',
        ]);
        $forgotResponse->assertSessionHas('status');

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($record);

        // 2. Access reset form
        $rawToken = 'sample-test-token-12345678901234567890';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($rawToken), 'created_at' => now()]
        );

        $formResponse = $this->get("/password/reset/{$rawToken}?email={$user->email}");
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Choose a New Password');

        // 3. Submit reset with valid token
        $resetResponse = $this->post('/password/reset', [
            'token' => $rawToken,
            'email' => $user->email,
            'password' => 'BrandNewPassword@999',
            'password_confirmation' => 'BrandNewPassword@999',
        ]);

        $resetResponse->assertRedirect('/login');
        $resetResponse->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword@999', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_role_middleware_enforces_strict_access_control(): void
    {
        $employeeUser = $this->createEmployeeUser();
        $hrAdminUser = $this->createHrAdmin();
        $superAdminUser = $this->createSuperAdmin();

        // Employee cannot access super-admin or hr-admin routes
        $this->actingAs($employeeUser)->get('/super-admin/dashboard')->assertStatus(403);
        $this->actingAs($employeeUser)->get('/hr-admin/dashboard')->assertStatus(403);

        // HR Admin cannot access super-admin settings
        $this->actingAs($hrAdminUser)->get('/super-admin/settings')->assertStatus(403);

        // HR Admin can access hr-admin routes
        $this->actingAs($hrAdminUser)->get('/hr-admin/dashboard')->assertStatus(200);

        // Super Admin can access both super-admin and hr-admin routes
        $this->actingAs($superAdminUser)->get('/super-admin/dashboard')->assertStatus(200);
        $this->actingAs($superAdminUser)->get('/hr-admin/dashboard')->assertStatus(200);
    }

    public function test_active_account_middleware_terminates_session_if_user_deactivated(): void
    {
        $user = User::factory()->create([
            'username' => 'active.first',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP5555',
            'first_name' => 'Active',
            'last_name' => 'First',
            'email' => 'active@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
        ]);

        // User is active initially
        $this->actingAs($user)->get('/employee/dashboard')->assertStatus(200);

        // Deactivate user in database
        $user->is_active = false;
        $user->save();

        // Subsequent request is terminated and redirected to login
        $response = $this->actingAs($user)->get('/employee/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_employee_own_data_access_guard_via_policies(): void
    {
        // Employee 1
        $user1 = User::factory()->create(['username' => 'emp1', 'role' => UserRole::EMPLOYEE]);
        $emp1 = Employee::factory()->create([
            'user_id' => $user1->id,
            'employee_code' => 'EMP0001',
            'first_name' => 'Employee',
            'last_name' => 'One',
            'email' => 'emp1@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
        ]);

        // Employee 2
        $user2 = User::factory()->create(['username' => 'emp2', 'role' => UserRole::EMPLOYEE]);
        $emp2 = Employee::factory()->create([
            'user_id' => $user2->id,
            'employee_code' => 'EMP0002',
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'email' => 'emp2@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $leaveType = LeaveType::first();
        $emp1Leave = LeaveRequest::create([
            'employee_id' => $emp1->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-16',
            'is_half_day' => false,
            'total_days' => 2.0,
            'reason' => 'Emp 1 Vacation',
            'status' => LeaveStatus::PENDING,
        ]);

        // Gate access-own-employee
        $this->assertTrue(Gate::forUser($user1)->allows('access-own-employee', $emp1->id));
        $this->assertFalse(Gate::forUser($user1)->allows('access-own-employee', $emp2->id));

        // Policies
        $this->assertTrue(Gate::forUser($user1)->allows('view', $emp1));
        $this->assertFalse(Gate::forUser($user1)->allows('view', $emp2));

        $this->assertTrue(Gate::forUser($user1)->allows('view', $emp1Leave));
        $this->assertFalse(Gate::forUser($user2)->allows('view', $emp1Leave));
    }
}
