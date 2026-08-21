<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrAdmin;
    protected Shift $shift;
    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'General Shift',
            'code' => 'GEN01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        $this->leaveType = LeaveType::create([
            'name' => 'Casual Leave',
            'slug' => 'casual-leave',
            'annual_quota' => 12.0,
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T184: Employee List & Search / Filter Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_view_employee_list_with_search_and_filter(): void
    {
        $user1 = User::factory()->create();
        $emp1 = Employee::factory()->create([
            'user_id' => $user1->id,
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'department' => 'Engineering',
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user2 = User::factory()->create();
        $emp2 = Employee::factory()->create([
            'user_id' => $user2->id,
            'first_name' => 'Diana',
            'last_name' => 'Prince',
            'department' => 'Marketing',
            'status' => EmployeeStatus::INACTIVE,
        ]);

        // Search by name
        $response = $this->actingAs($this->hrAdmin)->get(route('hr-admin.employees.index', ['search' => 'Charlie']));
        $response->assertOk();
        $response->assertSee('Charlie');
        $response->assertDontSee('Diana');

        // Filter by status
        $responseStatus = $this->actingAs($this->hrAdmin)->get(route('hr-admin.employees.index', ['status' => 'inactive']));
        $responseStatus->assertOk();
        $responseStatus->assertSee('Diana');
        $responseStatus->assertDontSee('Charlie');
    }

    /*
    |--------------------------------------------------------------------------
    | T184: Employee Creation Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_create_employee_with_user_account_and_initial_balances(): void
    {
        $payload = [
            'first_name' => 'Edward',
            'last_name' => 'Norton',
            'role' => 'employee',
            'email' => 'edward.norton@hrm.local',
            'phone' => '+15550198822',
            'gender' => 'male',
            'date_of_birth' => '1992-05-14',
            'joining_date' => '2026-01-15',
            'department' => 'Product',
            'designation' => 'Senior PM',
            'monthly_salary' => 75000.00,
            'shift_id' => $this->shift->id,
            'employee_code' => 'EMP9901',
            'status' => 'active',
            'auto_generate_password' => 1,
            'bank_name' => 'First Tech Bank',
            'account_number' => '998877665544',
            'ifsc_code' => 'FTBK0009988',
            'pan_number' => 'ABCDE9901F',
        ];

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.employees.store'), $payload);

        $employee = Employee::where('email', 'edward.norton@hrm.local')->first();
        $this->assertNotNull($employee);

        $response->assertRedirect(route('hr-admin.employees.show', $employee->id));
        $response->assertSessionHas('success');

        // 1. Employee record created
        $this->assertEquals('EMP9901', $employee->employee_code);
        $this->assertEquals(75000.00, (float) $employee->monthly_salary);

        // 2. Linked User account created
        $user = User::where('email', 'edward.norton@hrm.local')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::EMPLOYEE, $user->role);
        $this->assertTrue($user->is_active);

        // 3. Initial leave balance allocated
        $this->assertDatabaseHas('employee_leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'allocated_days' => 12.0,
        ]);

        // 4. Audit Log entry generated
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.created',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T184: Employee Edit & Sync Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_update_employee_and_sync_user_account(): void
    {
        $user = User::factory()->create([
            'email' => 'frank.old@hrm.local',
            'name' => 'Frank Miller',
        ]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $this->shift->id,
            'first_name' => 'Frank',
            'last_name' => 'Miller',
            'email' => 'frank.old@hrm.local',
            'monthly_salary' => 50000.00,
            'bank_name' => 'Old Bank',
            'account_number' => '1122334455',
            'ifsc_code' => 'OLDB0001122',
            'pan_number' => 'ABCDE1122F',
        ]);

        $payload = [
            'first_name' => 'Franklin',
            'last_name' => 'Miller',
            'role' => 'employee',
            'email' => 'franklin.updated@hrm.local',
            'phone' => '+15550193344',
            'gender' => 'male',
            'date_of_birth' => '1990-03-20',
            'joining_date' => '2026-02-01',
            'department' => 'Security',
            'designation' => 'Lead Engineer',
            'monthly_salary' => 65000.00,
            'shift_id' => $this->shift->id,
            'employee_code' => $employee->employee_code,
            'status' => 'active',
            'bank_name' => 'Global Union Bank',
            'account_number' => '5566778899',
            'ifsc_code' => 'GLOB0005566',
            'pan_number' => 'ABCDE5566F',
        ];

        $response = $this->actingAs($this->hrAdmin)->put(route('hr-admin.employees.update', $employee->id), $payload);

        $response->assertRedirect(route('hr-admin.employees.show', $employee->id));
        $response->assertSessionHas('success');

        // Check updated employee
        $employee->refresh();
        $this->assertEquals('Franklin', $employee->first_name);
        $this->assertEquals('franklin.updated@hrm.local', $employee->email);
        $this->assertEquals(65000.00, (float) $employee->monthly_salary);

        // Check synced user
        $user->refresh();
        $this->assertEquals('Franklin Miller', $user->name);
        $this->assertEquals('franklin.updated@hrm.local', $user->email);

        // Check Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.updated',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T184: Employee Status Change Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_update_employee_status_and_deactivate_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.employees.update-status', $employee->id), [
            'status' => 'resigned',
            'status_change_reason' => 'Relocating to another state.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $employee->refresh();
        $this->assertEquals(EmployeeStatus::RESIGNED, $employee->status);
        $this->assertEquals('Relocating to another state.', $employee->status_change_reason);

        // Linked User is deactivated
        $user->refresh();
        $this->assertFalse($user->is_active);

        // Audit Log entry generated
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.status_changed',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T184: Validation Error Handling Tests
    |--------------------------------------------------------------------------
    */
    public function test_employee_creation_fails_with_duplicate_code_or_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'duplicate@hrm.local']);
        $existingEmployee = Employee::factory()->create([
            'user_id' => $existingUser->id,
            'employee_code' => 'EMP1001',
            'email' => 'duplicate@hrm.local',
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.employees.store'), [
            'first_name' => 'George',
            'last_name' => 'Costanza',
            'email' => 'duplicate@hrm.local', // Duplicate
            'employee_code' => 'EMP1001', // Duplicate
            'phone' => '+15550192233',
            'gender' => 'male',
            'date_of_birth' => '1991-01-01',
            'joining_date' => '2026-03-01',
            'department' => 'Operations',
            'designation' => 'Assistant',
            'monthly_salary' => 45000.00,
            'shift_id' => $this->shift->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email', 'employee_code']);
    }
}
