<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase18AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@hrm.local',
            'password' => Hash::make('AdminSecret123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::factory()->create([
            'email' => 'hradmin@hrm.local',
            'password' => Hash::make('HrAdminSecret123!'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'email' => 'employee@hrm.local',
            'password' => Hash::make('EmployeeSecret123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'email' => 'employee@hrm.local',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T182: Login Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_user_can_login_with_valid_credentials_and_redirect_to_dashboard(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'super_admin',
            'username' => $this->superAdmin->username,
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    public function test_hr_admin_and_employee_redirect_to_respective_dashboards_on_login(): void
    {
        // HR Admin login
        $responseHr = $this->post(route('login.post'), [
            'role' => 'hr_admin',
            'username' => $this->hrAdmin->username,
            'password' => 'HrAdminSecret123!',
        ]);
        $responseHr->assertRedirect(route('hr-admin.dashboard'));
        $this->assertAuthenticatedAs($this->hrAdmin);

        auth()->logout();
        $this->flushSession();

        // Employee login
        $responseEmp = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => $this->employeeUser->username,
            'password' => 'EmployeeSecret123!',
        ]);
        $responseEmp->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($this->employeeUser);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => $this->employeeUser->username,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_deactivated_account_is_prevented_from_logging_in(): void
    {
        $this->employeeUser->update(['is_active' => false]);

        $response = $this->post(route('login.post'), [
            'role' => 'employee',
            'username' => $this->employeeUser->username,
            'password' => 'EmployeeSecret123!',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | T182: Logout Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | T182: Password Change Tests
    |--------------------------------------------------------------------------
    */
    public function test_authenticated_user_can_change_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->employeeUser)->post(route('password.change.post'), [
            'current_password' => 'EmployeeSecret123!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->employeeUser->fresh()->password));
    }

    public function test_password_change_fails_if_current_password_is_incorrect(): void
    {
        $response = $this->actingAs($this->employeeUser)->post(route('password.change.post'), [
            'current_password' => 'IncorrectOldPassword!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertTrue(Hash::check('EmployeeSecret123!', $this->employeeUser->fresh()->password));
    }

    /*
    |--------------------------------------------------------------------------
    | T182: Password Forgot & Reset Tests
    |--------------------------------------------------------------------------
    */
    public function test_user_can_request_password_reset_link(): void
    {
        $response = $this->post(route('password.forgot.post'), [
            'username' => $this->employeeUser->username,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'employee@hrm.local',
        ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $rawToken = 'valid_secret_reset_token_xyz';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'employee@hrm.local'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.reset.post'), [
            'token' => $rawToken,
            'email' => 'employee@hrm.local',
            'password' => 'CompletelyNewPass123!',
            'password_confirmation' => 'CompletelyNewPass123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('CompletelyNewPass123!', $this->employeeUser->fresh()->password));
    }

    public function test_password_reset_fails_with_expired_or_invalid_token(): void
    {
        $rawToken = 'valid_token_but_expired';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'employee@hrm.local'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => Carbon::now()->subMinutes(120),
            ]
        );

        $response = $this->post(route('password.reset.post'), [
            'token' => $rawToken,
            'email' => 'employee@hrm.local',
            'password' => 'AnotherNewPass123!',
            'password_confirmation' => 'AnotherNewPass123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertTrue(Hash::check('EmployeeSecret123!', $this->employeeUser->fresh()->password));
    }
}
