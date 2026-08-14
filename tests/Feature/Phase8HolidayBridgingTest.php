<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\HolidayBridgingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8HolidayBridgingTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;
    protected HolidayBridgingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->service = new HolidayBridgingService();

        $user = User::factory()->create(['role' => 'employee']);
        $shift = Shift::firstOrCreate(
            ['code' => 'GEN-001'],
            [
                'name' => 'General Day Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'is_active' => true,
            ]
        );

        $this->employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 60000.00,
        ]);
    }

    public function test_holiday_bridged_when_surrounded_by_unapproved_absence_on_both_sides(): void
    {
        // 2026-08-14 (Friday) = Absent
        // 2026-08-15 (Saturday) = Holiday (Independence Day)
        // 2026-08-17 (Monday) = Absent (Sunday 2026-08-16 is week-off)
        Holiday::firstOrCreate(
            ['holiday_date' => '2026-08-15'],
            ['name' => 'Independence Day', 'description' => 'National Holiday']
        );

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-14',
            'status' => AttendanceStatus::ABSENT,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-17',
            'status' => AttendanceStatus::ABSENT,
        ]);

        $result = $this->service->detectBridgedHolidaysForEmployee($this->employee->id, 2026, 8);

        $this->assertEquals(1, $result['bridged_count']);
        $this->assertContains('2026-08-15', $result['bridged_holidays']);
    }

    public function test_holiday_not_bridged_when_preceding_day_is_approved_leave(): void
    {
        Holiday::firstOrCreate(
            ['holiday_date' => '2026-08-15'],
            ['name' => 'Independence Day', 'description' => 'National Holiday']
        );

        $leaveType = LeaveType::firstOrCreate(
            ['slug' => 'casual-leave'],
            ['name' => 'Casual Leave', 'annual_quota' => 12, 'is_active' => true]
        );

        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-14',
            'end_date' => '2026-08-14',
            'total_days' => 1.0,
            'status' => LeaveStatus::APPROVED,
            'reason' => 'Family event',
            'reviewed_at' => now(),
        ]);

        // Succeeding day is absent
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-17',
            'status' => AttendanceStatus::ABSENT,
        ]);

        $result = $this->service->detectBridgedHolidaysForEmployee($this->employee->id, 2026, 8);

        $this->assertEquals(0, $result['bridged_count']);
        $this->assertEmpty($result['bridged_holidays']);
    }

    public function test_holiday_not_bridged_when_succeeding_day_is_approved_leave(): void
    {
        Holiday::firstOrCreate(
            ['holiday_date' => '2026-08-15'],
            ['name' => 'Independence Day', 'description' => 'National Holiday']
        );

        $leaveType = LeaveType::firstOrCreate(
            ['slug' => 'casual-leave'],
            ['name' => 'Casual Leave', 'annual_quota' => 12, 'is_active' => true]
        );

        // Preceding day is absent
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-14',
            'status' => AttendanceStatus::ABSENT,
        ]);

        // Succeeding day is approved leave
        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-17',
            'end_date' => '2026-08-17',
            'total_days' => 1.0,
            'status' => LeaveStatus::APPROVED,
            'reason' => 'Personal work',
            'reviewed_at' => now(),
        ]);

        $result = $this->service->detectBridgedHolidaysForEmployee($this->employee->id, 2026, 8);

        $this->assertEquals(0, $result['bridged_count']);
        $this->assertEmpty($result['bridged_holidays']);
    }

    public function test_holiday_not_bridged_when_preceding_or_succeeding_day_is_present(): void
    {
        Holiday::firstOrCreate(
            ['holiday_date' => '2026-08-15'],
            ['name' => 'Independence Day', 'description' => 'National Holiday']
        );

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-14',
            'status' => AttendanceStatus::PRESENT,
            'punch_in_at' => '2026-08-14 08:58:00',
            'punch_out_at' => '2026-08-14 18:05:00',
            'total_hours' => 9.1,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-17',
            'status' => AttendanceStatus::ABSENT,
        ]);

        $result = $this->service->detectBridgedHolidaysForEmployee($this->employee->id, 2026, 8);

        $this->assertEquals(0, $result['bridged_count']);
        $this->assertEmpty($result['bridged_holidays']);
    }

    public function test_is_holiday_bridged_helper_direct_assertions(): void
    {
        $this->assertTrue($this->service->isHolidayBridged('2026-08-15', 'absent', 'absent', false, false));
        $this->assertFalse($this->service->isHolidayBridged('2026-08-15', 'leave', 'absent', true, false));
        $this->assertFalse($this->service->isHolidayBridged('2026-08-15', 'absent', 'leave', false, true));
        $this->assertFalse($this->service->isHolidayBridged('2026-08-15', 'present', 'absent', false, false));
        $this->assertFalse($this->service->isHolidayBridged('2026-08-15', 'absent', 'present', false, false));
    }
}
