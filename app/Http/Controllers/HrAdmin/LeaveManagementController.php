<?php

namespace App\Http\Controllers\HrAdmin;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\AllocateLeaveRequest;
use App\Http\Requests\Leave\RejectLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Audit\AuditLoggerService;
use App\Services\Leave\LeaveAttendanceSyncService;
use App\Services\Leave\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveManagementController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $balanceService,
        protected LeaveAttendanceSyncService $attendanceSyncService,
        protected AuditLoggerService $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Display company-wide leave approvals and history.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $departmentFilter = $request->query('department');
        $employeeFilter = $request->query('employee_id');

        $query = LeaveRequest::with(['employee.user', 'leaveType', 'reviewer'])
            ->latest('created_at');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($employeeFilter) {
            $query->where('employee_id', $employeeFilter);
        }

        if ($departmentFilter) {
            $query->whereHas('employee', function ($q) use ($departmentFilter) {
                $q->where('department', $departmentFilter);
            });
        }

        $leaveRequests = $query->paginate(20)->withQueryString();

        // Metrics Summary
        $stats = [
            'pending_count' => LeaveRequest::where('status', LeaveStatus::PENDING)->count(),
            'approved_count' => LeaveRequest::where('status', LeaveStatus::APPROVED)->count(),
            'rejected_count' => LeaveRequest::where('status', LeaveStatus::REJECTED)->count(),
            'total_requests' => LeaveRequest::count(),
        ];

        $employees = Employee::orderBy('first_name')->get();
        $departments = Employee::distinct()->pluck('department')->filter()->values();

        return view('hr-admin.leaves.index', compact('leaveRequests', 'stats', 'employees', 'departments', 'statusFilter', 'departmentFilter', 'employeeFilter'));
    }

    /**
     * Approve a pending leave request.
     */
    public function approve(int $id): RedirectResponse
    {
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType'])->findOrFail($id);

        if ($leaveRequest->status !== LeaveStatus::PENDING) {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;
        $year = (int) Carbon::parse($leaveRequest->start_date)->year;

        // Check sufficient balance before approval
        $hasBalance = $this->balanceService->hasSufficientBalance(
            $employee->id,
            $leaveType->id,
            (float) $leaveRequest->total_days,
            $year
        );

        if (!$hasBalance) {
            return back()->with('error', "Cannot approve: Employee only has {$this->balanceService->getBalance($employee->id, $leaveType->id, $year)} days available for {$leaveType->name}.");
        }

        $oldStatus = $leaveRequest->status->value;

        DB::transaction(function () use ($leaveRequest, $employee, $leaveType, $year) {
            // 1. Mark request as approved
            $leaveRequest->update([
                'status' => LeaveStatus::APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => Carbon::now(),
            ]);

            // 2. Deduct days from employee balance
            $this->balanceService->deductBalance(
                $employee->id,
                $leaveType->id,
                (float) $leaveRequest->total_days,
                $year
            );

            // 3. Sync to attendance records table
            $this->attendanceSyncService->syncApprovedLeave($leaveRequest);
        });

        // 4. In-App Notification (T166)
        $this->notificationService->notifyLeaveStatus($leaveRequest, 'approved');

        // 5. Audit Trail
        $this->auditLogger->log(
            action: 'leave.approved',
            targetType: LeaveRequest::class,
            targetId: $leaveRequest->id,
            beforeValues: ['status' => $oldStatus],
            afterValues: [
                'status' => LeaveStatus::APPROVED->value,
                'reviewed_by' => Auth::id(),
                'total_days_deducted' => $leaveRequest->total_days,
            ]
        );

        return back()->with('success', "Leave request #{$leaveRequest->id} for {$employee->full_name} ({$leaveRequest->total_days} days) approved successfully and synced to attendance.");
    }

    /**
     * Reject a pending leave request with a mandatory reason.
     */
    public function reject(RejectLeaveRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();
        $leaveRequest = LeaveRequest::with(['employee.user', 'leaveType'])->findOrFail($id);

        if ($leaveRequest->status !== LeaveStatus::PENDING) {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $oldStatus = $leaveRequest->status->value;
        $rejectionReason = $request->input('rejection_reason');

        $leaveRequest->update([
            'status' => LeaveStatus::REJECTED,
            'rejection_reason' => $rejectionReason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now(),
        ]);

        // In-App Notification (T166)
        $this->notificationService->notifyLeaveStatus($leaveRequest, 'rejected', $rejectionReason);

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave.rejected',
            targetType: LeaveRequest::class,
            targetId: $leaveRequest->id,
            beforeValues: ['status' => $oldStatus],
            afterValues: [
                'status' => LeaveStatus::REJECTED->value,
                'rejection_reason' => $rejectionReason,
                'reviewed_by' => Auth::id(),
            ]
        );

        return back()->with('success', "Leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name} rejected.");
    }

    /**
     * Display Leave Types & Allocations Management.
     */
    public function types(): View
    {
        $leaveTypes = LeaveType::withCount('balances')->get();
        $employees = Employee::with('leaveBalances.leaveType')->orderBy('first_name')->get();
        $currentYear = (int) date('Y');

        return view('hr-admin.leaves.types', compact('leaveTypes', 'employees', 'currentYear'));
    }

    /**
     * Store a new leave type.
     */
    public function storeType(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $leaveType = LeaveType::create([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'annual_quota' => $request->input('annual_quota'),
            'requires_document' => (bool) $request->input('requires_document', false),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave_type.created',
            targetType: LeaveType::class,
            targetId: $leaveType->id,
            beforeValues: null,
            afterValues: $leaveType->toArray()
        );

        return back()->with('success', "Leave type '{$leaveType->name}' created successfully.");
    }

    /**
     * Update an existing leave type.
     */
    public function updateType(UpdateLeaveTypeRequest $request, int $id): RedirectResponse
    {
        $leaveType = LeaveType::findOrFail($id);
        $oldValues = $leaveType->toArray();

        $leaveType->update([
            'name' => $request->input('name'),
            'annual_quota' => $request->input('annual_quota'),
            'requires_document' => (bool) $request->input('requires_document', false),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave_type.updated',
            targetType: LeaveType::class,
            targetId: $leaveType->id,
            beforeValues: $oldValues,
            afterValues: $leaveType->toArray()
        );

        return back()->with('success', "Leave type '{$leaveType->name}' updated successfully.");
    }

    /**
     * Set or update an employee's leave balance allocation for a cycle.
     */
    public function storeAllocation(AllocateLeaveRequest $request): RedirectResponse
    {
        $employeeId = (int) $request->input('employee_id');
        $leaveTypeId = (int) $request->input('leave_type_id');
        $year = (int) $request->input('year');
        $allocatedDays = (float) $request->input('allocated_days');

        $balance = EmployeeLeaveBalance::firstOrNew([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
        ]);

        $oldValues = $balance->exists ? $balance->toArray() : null;
        $usedDays = (float) ($balance->used_days ?? 0.0);
        $remainingDays = max(0.0, $allocatedDays - $usedDays);

        $balance->allocated_days = $allocatedDays;
        $balance->used_days = $usedDays;
        $balance->remaining_days = $remainingDays;
        $balance->save();

        // Audit Trail
        $this->auditLogger->log(
            action: 'leave.allocated',
            targetType: EmployeeLeaveBalance::class,
            targetId: $balance->id,
            beforeValues: $oldValues,
            afterValues: $balance->toArray()
        );

        return back()->with('success', "Leave quota allocated: {$allocatedDays} days ({$remainingDays} remaining) for year {$year}.");
    }
}
