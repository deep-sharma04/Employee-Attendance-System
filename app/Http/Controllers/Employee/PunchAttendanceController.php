<?php

namespace App\Http\Controllers\Employee;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Attendance\AttendanceAggregationService;
use App\Services\Attendance\AttendanceClassificationService;
use App\Services\Attendance\IpValidationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PunchAttendanceController extends Controller
{
    public function __construct(
        protected IpValidationService $ipValidator,
        protected AttendanceClassificationService $classifier,
        protected AttendanceAggregationService $aggregator
    ) {}

    protected function getOrCreateEmployee(User $user): ?Employee
    {
        if ($user->isSuperAdmin() || $user->isHrAdmin() || $user->isClient()) {
            return null;
        }

        $employee = Employee::with('shift')->where('user_id', $user->id)->first();

        if (!$employee) {
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? $user->name;
            $lastName = $nameParts[1] ?? 'User';

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_code' => 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'department' => $user->isManager() ? 'Management' : ($user->isTeamLead() ? 'Team Leadership' : 'Engineering'),
                'designation' => $user->role->label(),
                'joining_date' => now()->toDateString(),
                'status' => \App\Enums\EmployeeStatus::ACTIVE,
            ]);
            $employee->load('shift');
        }

        return $employee;
    }

    /**
     * Record an employee's daily punch-in.
     */
    public function punchIn(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->isHrAdmin() || $user->isClient()) {
            return back()->with('error', 'Punch operations are not applicable for Administrator accounts.');
        }

        $employee = $this->getOrCreateEmployee($user);

        if (!$employee) {
            return back()->with('error', 'No active employee profile linked to this account.');
        }

        $clientIp = $request->ip();

        // 1. Office IP Validation
        if (!$this->ipValidator->isIpAllowed($clientIp)) {
            return back()->with('error', "Punch rejected: You are connected to an unauthorized network (IP: {$clientIp}). Please connect to the approved office network.");
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 2. Prevent duplicate punch-in
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($record && $record->punch_in_at) {
            return back()->with('warning', "You have already punched in for today at {$record->punch_in_at->format('H:i:s')}.");
        }

        // 3. Classify status based on shift timings
        $shift = $employee->shift;
        $shiftStartTime = $shift ? substr($shift->start_time, 0, 5) : '09:00';
        $graceMinutes = $shift ? $shift->grace_period_minutes : 15;
        $halfDayMinutes = $shift ? $shift->half_day_threshold_minutes : 60;

        $status = $this->classifier->classifyPunchIn($now, $shiftStartTime, $graceMinutes, $halfDayMinutes);

        if (!$record) {
            $record = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift?->id,
                'attendance_date' => $today,
                'punch_in' => $now->format('H:i:s'),
                'punch_in_at' => $now,
                'punch_in_ip' => $clientIp,
                'status' => $status,
                'total_working_hours' => 0.0,
                'total_hours' => 0.0,
                'is_manually_corrected' => false,
            ]);
        } else {
            $record->update([
                'punch_in' => $now->format('H:i:s'),
                'punch_in_at' => $now,
                'punch_in_ip' => $clientIp,
                'status' => $status,
            ]);
        }

        // 4. Log Raw Punch Event
        AttendanceEvent::create([
            'employee_id' => $employee->id,
            'action' => 'punch_in',
            'event_timestamp' => $now,
            'ip_address' => $clientIp,
            'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 255),
            'is_valid' => true,
        ]);

        $statusLabel = match ($status) {
            AttendanceStatus::PRESENT => 'On-Time',
            AttendanceStatus::LATE => 'Late',
            AttendanceStatus::HALF_DAY => 'Half Day',
            default => $status->value,
        };

        return back()->with('success', "Punched in successfully at {$now->format('H:i:s')} (Status: {$statusLabel}).");
    }

    /**
     * Record an employee's daily punch-out.
     */
    public function punchOut(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->isHrAdmin() || $user->isClient()) {
            return back()->with('error', 'Punch operations are not applicable for Administrator accounts.');
        }

        $employee = $this->getOrCreateEmployee($user);

        if (!$employee) {
            return back()->with('error', 'No active employee profile linked to this account.');
        }

        $clientIp = $request->ip();

        // 1. Office IP Validation
        if (!$this->ipValidator->isIpAllowed($clientIp)) {
            return back()->with('error', "Punch rejected: You are connected to an unauthorized network (IP: {$clientIp}). Please connect to the approved office network.");
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 2. Validate prior punch-in exists
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$record || !$record->punch_in) {
            return back()->with('error', 'You must punch in before recording a punch out for today.');
        }

        if ($record->punch_out) {
            return back()->with('warning', "You have already completed your punch out for today at {$record->punch_out}.");
        }

        // 3. Compute total hours
        $punchInTime = Carbon::parse("{$today} {$record->punch_in}");
        $totalMinutes = abs($now->diffInMinutes($punchInTime));
        $totalHours = round($totalMinutes / 60.0, 2);

        $record->update([
            'punch_out' => $now->format('H:i:s'),
            'punch_out_at' => $now,
            'punch_out_ip' => $clientIp,
            'total_working_hours' => $totalHours,
            'total_hours' => $totalHours,
        ]);

        // 4. Log Raw Punch Event
        AttendanceEvent::create([
            'employee_id' => $employee->id,
            'action' => 'punch_out',
            'event_timestamp' => $now,
            'ip_address' => $clientIp,
            'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 255),
            'is_valid' => true,
        ]);

        return back()->with('success', "Punched out successfully at {$now->format('H:i:s')} (Total shift duration: {$totalHours} hours).");
    }

    /**
     * Display personal attendance history for the authenticated employee.
     */
    public function history(Request $request): View
    {
        $user = Auth::user();
        $employee = $this->getOrCreateEmployee($user);

        if (!$employee) {
            abort(403, 'Attendance history is not available for administrator accounts.');
        }

        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));

        $summary = $this->aggregator->aggregateMonthlyAttendance($employee->id, $year, $month);

        $records = AttendanceRecord::with('events')
            ->where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return view('employee.attendance.history', [
            'employee' => $employee,
            'records' => $records,
            'summary' => $summary,
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }
}
