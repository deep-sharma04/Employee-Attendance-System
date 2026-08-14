<?php

namespace App\Http\Controllers\HrAdmin;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceCorrectionRequest;
use App\Http\Requests\Attendance\StoreManualAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Audit\AuditLoggerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display company-wide attendance monitoring dashboard with filters.
     */
    public function index(Request $request): View
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $department = $request->input('department');
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Employee::with([
            'shift',
            'attendanceRecords' => fn($q) => $q->whereDate('attendance_date', $selectedDate),
        ])->where('status', 'active');

        if (!empty($department)) {
            $query->where('department', $department);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('first_name')->paginate(20)->withQueryString();

        // Calculate summary metrics for the selected date
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        $recordsForDate = AttendanceRecord::whereDate('attendance_date', $selectedDate)->get();
        $presentCount = $recordsForDate->where('status', AttendanceStatus::PRESENT)->count();
        $lateCount = $recordsForDate->where('status', AttendanceStatus::LATE)->count();
        $halfDayCount = $recordsForDate->where('status', AttendanceStatus::HALF_DAY)->count();
        $missingPunchOutCount = $recordsForDate->filter(fn($r) => $r->punch_in_at && !$r->punch_out_at && Carbon::parse($selectedDate)->isPast() && !Carbon::parse($selectedDate)->isToday())->count();

        $absentCount = max(0, $totalActiveEmployees - ($presentCount + $lateCount + $halfDayCount));

        $departments = Employee::distinct()->whereNotNull('department')->pluck('department')->sort()->values();

        return view('hr-admin.attendance.index', [
            'employees' => $employees,
            'selectedDate' => $selectedDate,
            'selectedDepartment' => $department,
            'selectedStatus' => $status,
            'search' => $search,
            'departments' => $departments,
            'metrics' => [
                'total_active' => $totalActiveEmployees,
                'present' => $presentCount,
                'late' => $lateCount,
                'half_day' => $halfDayCount,
                'absent' => $absentCount,
                'missing_punch_out' => $missingPunchOutCount,
            ],
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    /**
     * Show the manual attendance correction form.
     */
    public function createCorrection($id): View
    {
        $record = AttendanceRecord::with(['employee.shift', 'events', 'corrector'])->findOrFail($id);

        return view('hr-admin.attendance.correct', [
            'record' => $record,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    /**
     * Store manual correction of an existing attendance record.
     */
    public function storeCorrection(StoreAttendanceCorrectionRequest $request, $id): RedirectResponse
    {
        $record = AttendanceRecord::with('employee')->findOrFail($id);
        $validated = $request->validated();

        $beforeValues = $record->toArray();

        $dateStr = $record->attendance_date->format('Y-m-d');
        $punchIn = !empty($validated['punch_in_at']) ? "{$dateStr} {$validated['punch_in_at']}:00" : $record->punch_in_at;
        $punchOut = !empty($validated['punch_out_at']) ? "{$dateStr} {$validated['punch_out_at']}:00" : $record->punch_out_at;

        $record->update([
            'status' => $validated['status'],
            'punch_in_at' => $punchIn,
            'punch_out_at' => $punchOut,
            'total_hours' => $validated['total_hours'] ?? $record->total_hours,
            'is_manually_corrected' => true,
            'corrected_by' => Auth::id(),
            'correction_reason' => $validated['correction_reason'],
            'corrected_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'attendance.corrected',
            targetType: 'App\Models\AttendanceRecord',
            targetId: $record->id,
            beforeValues: $beforeValues,
            afterValues: $record->fresh()->toArray(),
            description: "Manual attendance correction for {$record->employee->full_name} on {$dateStr}. Reason: {$validated['correction_reason']}"
        );

        return redirect()->route('hr-admin.attendance.index', ['date' => $dateStr])
            ->with('success', "Attendance record for {$record->employee->full_name} on {$dateStr} corrected successfully.");
    }

    /**
     * Add a historical missing attendance record for an employee.
     */
    public function storeManualEntry(StoreManualAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $employee = Employee::with('shift')->findOrFail($validated['employee_id']);
        $dateStr = Carbon::parse($validated['attendance_date'])->toDateString();

        $punchIn = !empty($validated['punch_in_at']) ? "{$dateStr} {$validated['punch_in_at']}:00" : null;
        $punchOut = !empty($validated['punch_out_at']) ? "{$dateStr} {$validated['punch_out_at']}:00" : null;

        $record = AttendanceRecord::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $dateStr,
            ],
            [
                'shift_id' => $employee->shift_id,
                'status' => $validated['status'],
                'punch_in_at' => $punchIn,
                'punch_out_at' => $punchOut,
                'punch_in_ip' => '127.0.0.1 (Manual Entry)',
                'punch_out_ip' => '127.0.0.1 (Manual Entry)',
                'total_hours' => $validated['total_hours'] ?? 8.0,
                'is_manually_corrected' => true,
                'corrected_by' => Auth::id(),
                'correction_reason' => $validated['correction_reason'],
                'corrected_at' => now(),
            ]
        );

        $this->auditLogger->log(
            action: 'attendance.manual_entry',
            targetType: 'App\Models\AttendanceRecord',
            targetId: $record->id,
            afterValues: $record->toArray(),
            description: "Added manual past attendance entry for {$employee->full_name} on {$dateStr}. Reason: {$validated['correction_reason']}"
        );

        return redirect()->route('hr-admin.attendance.index', ['date' => $dateStr])
            ->with('success', "Manual attendance added for {$employee->full_name} on {$dateStr}.");
    }
}
