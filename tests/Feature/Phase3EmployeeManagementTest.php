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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_hr_admin_can_view_employee_listing_with_search_and_filters(): void
    {
        $shift = Shift::first();

        $emp1 = Employee::factory()->create([
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'department' => 'Operations',
            'designation' => 'Operations Lead',
            'status' => EmployeeStatus::ACTIVE,
            'shift_id' => $shift->id,
        ]);

        $emp2 = Employee::factory()->create([
            'first_name' => 'David',
            'last_name' => 'Miller',
            'department' => 'Engineering',
            'designation' => 'DevOps Engineer',
            'status' => EmployeeStatus::TERMINATED,
            'shift_id' => $shift->id,
        ]);

        // 1. Unfiltered list
        $response = $this->actingAsHrAdmin()->get('/hr-admin/employees');
        $response->assertStatus(200);
        $response->assertSee('Charlie Brown');
        $response->assertSee('David Miller');

        // 2. Search filter
        $searchResponse = $this->actingAsHrAdmin()->get('/hr-admin/employees?search=Charlie');
        $searchResponse->assertSee('Charlie Brown');
        $searchResponse->assertDontSee('David Miller');

        // 3. Department filter
        $deptResponse = $this->actingAsHrAdmin()->get('/hr-admin/employees?department=Engineering');
        $deptResponse->assertSee('David Miller');
        $deptResponse->assertDontSee('Charlie Brown');

        // 4. Status filter
        $statusResponse = $this->actingAsHrAdmin()->get('/hr-admin/employees?status=active');
        $statusResponse->assertSee('Charlie Brown');
        $statusResponse->assertDontSee('David Miller');
    }

    public function test_employee_creation_form_renders_with_active_shifts_and_leave_types(): void
    {
        $response = $this->actingAsHrAdmin()->get('/hr-admin/employees/create');
        $response->assertStatus(200);
        $response->assertSee('Add New Employee Profile');
        $response->assertSee('General Day Shift');
        $response->assertSee('Casual Leave');
        $response->assertSee('Medical Leave');
    }

    public function test_employee_creation_validation_rules_enforce_data_integrity(): void
    {
        $response = $this->actingAsHrAdmin()->post('/hr-admin/employees', []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'date_of_birth',
            'department',
            'designation',
            'joining_date',
            'shift_id',
            'monthly_salary',
            'bank_name',
            'account_number',
            'ifsc_code',
            'pan_number',
        ]);
    }

    public function test_hr_admin_can_successfully_create_employee_with_leave_balances_and_audit(): void
    {
        $shift = Shift::first();
        $casualType = LeaveType::where('slug', 'casual')->first();
        $medicalType = LeaveType::where('slug', 'medical')->first();

        $payload = [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'role' => 'employee',
            'email' => 'michael.scott@hrm.local',
            'phone' => '+91 9988776655',
            'gender' => 'male',
            'date_of_birth' => '1985-03-15',
            'department' => 'Management',
            'designation' => 'Regional Manager',
            'joining_date' => '2026-02-01',
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 95000.00,
            'bank_name' => 'State Bank of India',
            'account_number' => '123456789012',
            'ifsc_code' => 'SBIN0001234',
            'pan_number' => 'ABCDE9999M',
            'leave_allocations' => [
                $casualType->id => 14.0,
                $medicalType->id => 12.0,
            ],
        ];

        $response = $this->actingAsHrAdmin()->post('/hr-admin/employees', $payload);

        $employee = Employee::where('email', 'michael.scott@hrm.local')->first();
        $this->assertNotNull($employee);
        $response->assertRedirect(route('hr-admin.employees.show', $employee->id));
        $response->assertSessionHas('created_employee_credentials');

        // Check linked User account
        $user = $employee->user;
        $this->assertNotNull($user);
        $this->assertEquals('Michael Scott', $user->name);
        $this->assertEquals(UserRole::EMPLOYEE, $user->role);
        $this->assertTrue($user->is_active);

        // Check leave balances
        $this->assertDatabaseHas('employee_leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $casualType->id,
            'allocated_days' => 14.0,
            'remaining_days' => 14.0,
        ]);

        // Check audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.created',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    public function test_hr_admin_can_edit_employee_and_syncs_user_profile(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'email' => 'pam.beesly@hrm.local',
            'monthly_salary' => 45000.00,
        ]);

        $editResponse = $this->actingAsHrAdmin()->get("/hr-admin/employees/{$employee->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Employee Profile');
        $editResponse->assertSee('Pam');

        $updateResponse = $this->actingAsHrAdmin()->put("/hr-admin/employees/{$employee->id}", [
            'first_name' => 'Pamela',
            'last_name' => 'Halpert',
            'role' => 'employee',
            'email' => 'pamela.halpert@hrm.local',
            'phone' => '+91 9123456789',
            'gender' => 'female',
            'date_of_birth' => '1990-05-10',
            'employee_code' => $employee->employee_code,
            'department' => 'Operations',
            'designation' => 'Office Administrator',
            'joining_date' => '2026-01-10',
            'shift_id' => $employee->shift_id,
            'status' => 'active',
            'monthly_salary' => 52000.00,
            'bank_name' => 'ICICI Bank',
            'account_number' => '987654321098',
            'ifsc_code' => 'ICIC0009876',
            'pan_number' => 'ABCDE8888P',
        ]);

        $updateResponse->assertRedirect(route('hr-admin.employees.show', $employee->id));

        $employee->refresh();
        $this->assertEquals('Pamela Halpert', $employee->full_name);
        $this->assertEquals('pamela.halpert@hrm.local', $employee->email);
        $this->assertEquals(52000.00, $employee->monthly_salary);

        // Verify User sync
        $this->assertEquals('Pamela Halpert', $employee->user->name);
        $this->assertEquals('pamela.halpert@hrm.local', $employee->user->email);

        // Verify Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.updated',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    public function test_hr_admin_can_update_employee_status_with_reason_and_deactivates_user(): void
    {
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $user = $employee->user;
        $this->assertTrue($user->is_active);

        $response = $this->actingAsHrAdmin()->post("/hr-admin/employees/{$employee->id}/status", [
            'status' => 'terminated',
            'status_change_reason' => 'Voluntary offboarding contract completion.',
        ]);

        $response->assertSessionHas('success');

        $employee->refresh();
        $user->refresh();

        $this->assertEquals(EmployeeStatus::TERMINATED, $employee->status);
        $this->assertEquals('Voluntary offboarding contract completion.', $employee->status_change_reason);
        $this->assertNotNull($employee->status_changed_at);
        $this->assertFalse($user->is_active);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.status_changed',
            'target_type' => 'App\Models\Employee',
            'target_id' => $employee->id,
        ]);
    }

    public function test_employee_can_view_own_profile_with_masked_bank_details(): void
    {
        $shift = Shift::first();
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'bank_name' => 'Citibank',
            'account_number' => '112233445566',
            'pan_number' => 'ABCDE1234J',
        ]);

        $casualType = LeaveType::where('slug', 'casual')->first();
        EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $casualType->id,
            'year' => (int) date('Y'),
            'allocated_days' => 12.0,
            'used_days' => 1.0,
            'remaining_days' => 11.0,
        ]);

        $response = $this->actingAs($user)->get('/employee/profile');
        $response->assertStatus(200);
        $response->assertSee('Jim Halpert');
        $response->assertSee('Citibank');
        $response->assertSee('5566'); // Last 4 digits visible
        $response->assertSee('11.0'); // Leave quota remaining
    }
}
