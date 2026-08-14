<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OfficeIpAllowlist;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18AttendancePunchTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Day Shift',
            'code' => 'DAY01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $this->shift->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T185: Punch In Flows
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_punch_in_successfully_from_authorized_ip(): void
    {
        Carbon::setTestNow('2026-08-10 09:05:00'); // Within grace period

        $response = $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-in'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertEquals('09:05:00', $record->punch_in_at->format('H:i:s'));

        // Event logged
        $this->assertDatabaseHas('attendance_events', [
            'employee_id' => $this->employee->id,
            'action' => 'punch_in',
        ]);
    }

    public function test_punch_in_beyond_grace_period_classifies_as_late(): void
    {
        Carbon::setTestNow('2026-08-10 09:30:00'); // Beyond 15 min grace, within 60 min threshold

        $response = $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-in'));

        $response->assertRedirect();

        $record = AttendanceRecord::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(AttendanceStatus::LATE, $record->status);
    }

    public function test_duplicate_punch_in_on_same_day_returns_warning(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        // First punch
        $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-in'));

        // Second punch attempt
        $response = $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-in'));

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $count = AttendanceRecord::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->count();

        $this->assertEquals(1, $count);
    }

    /*
    |--------------------------------------------------------------------------
    | T185: Punch Out Flow
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_punch_out_and_calculate_total_hours(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        // Punch In
        $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-in'));

        // Advance time by 8 hours
        Carbon::setTestNow('2026-08-10 17:00:00');

        // Punch Out
        $response = $this->actingAs($this->employeeUser)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('employee.attendance.punch-out'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->first();

        $this->assertNotNull($record->punch_out_at);
        $this->assertEquals(8.0, (float) $record->total_hours);

        $this->assertDatabaseHas('attendance_events', [
            'employee_id' => $this->employee->id,
            'action' => 'punch_out',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T185: HR Admin Correction & Manual Entry Workflows
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_correct_existing_attendance_record(): void
    {
        $record = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
            'attendance_date' => '2026-08-05',
            'status' => AttendanceStatus::LATE,
            'punch_in_at' => '2026-08-05 09:45:00',
            'punch_out_at' => '2026-08-05 18:00:00',
            'total_hours' => 8.25,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.attendance.store-correction', $record->id), [
            'status' => 'present',
            'punch_in_at' => '09:00',
            'punch_out_at' => '18:00',
            'total_hours' => 9.0,
            'correction_reason' => 'Client meeting offsite in morning confirmed by manager.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record->refresh();
        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertTrue((bool) $record->is_manually_corrected);
        $this->assertEquals('Client meeting offsite in morning confirmed by manager.', $record->correction_reason);
        $this->assertEquals(9.0, (float) $record->total_hours);

        // Audit Log entry generated
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.corrected',
            'target_type' => 'App\Models\AttendanceRecord',
            'target_id' => $record->id,
        ]);
    }

    public function test_hr_admin_can_store_manual_past_attendance_entry(): void
    {
        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.attendance.store-manual'), [
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
            'punch_in_at' => '09:00',
            'punch_out_at' => '18:00',
            'total_hours' => 9.0,
            'correction_reason' => 'Retroactive manual logging for field deployment.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-08-01')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertTrue((bool) $record->is_manually_corrected);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.manual_entry',
            'target_type' => 'App\Models\AttendanceRecord',
            'target_id' => $record->id,
        ]);
    }
}
