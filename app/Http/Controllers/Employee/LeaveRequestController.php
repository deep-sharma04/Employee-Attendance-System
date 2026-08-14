<?php

namespace App\Http\Controllers\Employee;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Audit\AuditLoggerService;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveWorkingDayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $balanceService,
        protected LeaveWorkingDayService $workingDayService,
        protected AuditLoggerService $auditLogger,
    ) {}

    /**
     * Show employee's leave balance cards and request history.
     */
    public function index(): View
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        $currentYear = (int) date('Y');

        $balances = $employee
            ? $this->balanceService->getBalancesForEmployee($employee->id, $currentYear)
            : collect();

        $leaveRequests = $employee
            ? LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15)
            : collect();

        return view('employee.leaves.index', compact('employee', 'balances', 'leaveRequests', 'currentYear'));
    }

    /**
     * Show leave application form.
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'No employee record linked to your account.');
        }

        $currentYear = (int) date('Y');
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $balances = $this->balanceService->getBalancesForEmployee($employee->id, $currentYear)
            ->keyBy('leave_type_id');

        return view('employee.leaves.create', compact('employee', 'leaveTypes', 'balances', 'currentYear'));
    }

    /**
     * Store new leave request.
     */
    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $isHalfDay = (bool) $request->input('is_half_day');
        $halfDayType = $isHalfDay ? $request->input('half_day_type') : null;

        $totalDays = $this->workingDayService->calculateWorkingDays($startDate, $endDate, $isHalfDay);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $request->input('leave_type_id'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_half_day' => $isHalfDay,
            'half_day_type' => $halfDayType,
            'total_days' => $totalDays,
            'reason' => $request->input('reason'),
            'status' => LeaveStatus::PENDING,
        ]);

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave.applied',
            targetType: LeaveRequest::class,
            targetId: $leaveRequest->id,
            beforeValues: null,
            afterValues: [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'status' => LeaveStatus::PENDING->value,
            ]
        );

        return redirect()->route('employee.leaves.index')->with('success', "Leave application for {$totalDays} day(s) submitted successfully for HR review.");
    }

    /**
     * Cancel an unapproved (pending) leave request.
     */
    public function cancel(int $id): RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // T094: Guard against cancelling approved leave
        if ($leaveRequest->status === LeaveStatus::APPROVED) {
            return back()->with('error', 'Approved leave requests cannot be cancelled by the employee. Please contact HR Administration.');
        }

        if ($leaveRequest->status !== LeaveStatus::PENDING) {
            return back()->with('error', 'Only pending leave requests can be cancelled.');
        }

        $oldStatus = $leaveRequest->status->value;
        $leaveRequest->update([
            'status' => LeaveStatus::CANCELLED,
            'cancelled_at' => Carbon::now(),
        ]);

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave.cancelled',
            targetType: LeaveRequest::class,
            targetId: $leaveRequest->id,
            beforeValues: ['status' => $oldStatus],
            afterValues: ['status' => LeaveStatus::CANCELLED->value, 'cancelled_at' => now()->toDateTimeString()]
        );

        return back()->with('success', 'Leave application cancelled successfully.');
    }
}
