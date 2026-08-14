<?php

namespace App\Http\Controllers\HrAdmin;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeStatusRequest;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a paginated employee list with search and filter.
     */
    public function index(Request $request): View
    {
        $query = Employee::with(['user', 'shift'])->latest();

        // Search query filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($dept = $request->input('department')) {
            $query->where('department', $dept);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->paginate(15)->withQueryString();

        $departments = Employee::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->orderBy('department')
            ->pluck('department');

        $statuses = EmployeeStatus::cases();

        return view('hr-admin.employees.index', [
            'employees' => $employees,
            'departments' => $departments,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'department', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $shifts = Shift::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();

        $nextCodeNumber = Employee::count() + 1;
        $suggestedCode = 'EMP' . str_pad((string) $nextCodeNumber, 4, '0', STR_PAD_LEFT);

        return view('hr-admin.employees.create', [
            'shifts' => $shifts,
            'leaveTypes' => $leaveTypes,
            'suggestedCode' => $suggestedCode,
            'statuses' => EmployeeStatus::cases(),
        ]);
    }

    /**
     * Store a newly created employee with user account and leave balances.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Determine username
        $username = $validated['username'] ?? null;
        if (empty($username)) {
            $baseUser = Str::slug($validated['first_name'] . '.' . $validated['last_name'], '.');
            $username = $baseUser;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUser . $counter++;
            }
        }

        // Determine password
        $rawPassword = $validated['password'] ?? null;
        if (empty($rawPassword) || $request->boolean('auto_generate_password')) {
            $rawPassword = 'Emp@' . Str::random(8) . '!';
        }

        // Determine employee code
        $employeeCode = $validated['employee_code'] ?? null;
        if (empty($employeeCode)) {
            $nextCodeNumber = Employee::count() + 1;
            $employeeCode = 'EMP' . str_pad((string) $nextCodeNumber, 4, '0', STR_PAD_LEFT);
            while (Employee::where('employee_code', $employeeCode)->exists()) {
                $nextCodeNumber++;
                $employeeCode = 'EMP' . str_pad((string) $nextCodeNumber, 4, '0', STR_PAD_LEFT);
            }
        }

        $employee = DB::transaction(function () use ($validated, $username, $rawPassword, $employeeCode) {
            // 1. Create linked user account
            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($rawPassword),
                'role' => UserRole::EMPLOYEE,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $employeeRole = Role::where('slug', UserRole::EMPLOYEE->value)->first();
            if ($employeeRole) {
                $user->roles()->syncWithoutDetaching([$employeeRole->id]);
            }

            // 2. Create Employee profile
            $emp = Employee::create([
                'user_id' => $user->id,
                'shift_id' => $validated['shift_id'],
                'employee_code' => $employeeCode,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'joining_date' => $validated['joining_date'],
                'department' => $validated['department'],
                'designation' => $validated['designation'],
                'status' => $validated['status'] ?? EmployeeStatus::ACTIVE,
                'monthly_salary' => $validated['monthly_salary'],
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'ifsc_code' => $validated['ifsc_code'],
                'pan_number' => $validated['pan_number'],
            ]);

            // 3. Allocate initial leave balances
            $activeLeaveTypes = LeaveType::where('is_active', true)->get();
            $allocations = $validated['leave_allocations'] ?? [];

            foreach ($activeLeaveTypes as $leaveType) {
                $quota = isset($allocations[$leaveType->id]) && is_numeric($allocations[$leaveType->id])
                    ? (float) $allocations[$leaveType->id]
                    : (float) $leaveType->annual_quota;

                EmployeeLeaveBalance::create([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => (int) date('Y'),
                    'allocated_days' => $quota,
                    'used_days' => 0.0,
                    'remaining_days' => $quota,
                ]);
            }

            // 4. Audit Log
            $this->auditLogger->log(
                action: 'employee.created',
                targetType: 'App\Models\Employee',
                targetId: $emp->id,
                afterValues: $emp->toArray(),
                description: "Created employee profile for {$emp->full_name} ({$emp->employee_code}) with username {$username}."
            );

            return $emp;
        });

        // Flash temporary credentials for HR communication
        session()->flash('created_employee_credentials', [
            'name' => $employee->full_name,
            'code' => $employee->employee_code,
            'username' => $username,
            'temporary_password' => $rawPassword,
        ]);

        return redirect()->route('hr-admin.employees.show', $employee->id)
            ->with('success', "Employee {$employee->full_name} ({$employee->employee_code}) created successfully.");
    }

    /**
     * Display the specified employee profile and summaries.
     */
    public function show($id): View
    {
        $employee = Employee::with([
            'user',
            'shift',
            'leaveBalances.leaveType',
            'leaveRequests.leaveType',
            'attendanceRecords' => fn($q) => $q->latest('attendance_date')->limit(10),
            'documents.documentType',
            'payrolls' => fn($q) => $q->latest('payroll_year')->latest('payroll_month')->limit(6),
        ])->findOrFail($id);

        return view('hr-admin.employees.show', [
            'employee' => $employee,
            'statuses' => EmployeeStatus::cases(),
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit($id): View
    {
        $employee = Employee::with(['user', 'shift'])->findOrFail($id);
        $shifts = Shift::where('is_active', true)->get();

        return view('hr-admin.employees.edit', [
            'employee' => $employee,
            'shifts' => $shifts,
            'statuses' => EmployeeStatus::cases(),
        ]);
    }

    /**
     * Update the specified employee record.
     */
    public function update(UpdateEmployeeRequest $request, $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validated();

        $beforeValues = $employee->toArray();

        DB::transaction(function () use ($employee, $validated, $beforeValues) {
            $employee->update($validated);

            // Sync user name & email
            if ($employee->user) {
                $employee->user->forceFill([
                    'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                    'email' => $validated['email'],
                ])->save();
            }

            $this->auditLogger->log(
                action: 'employee.updated',
                targetType: 'App\Models\Employee',
                targetId: $employee->id,
                beforeValues: $beforeValues,
                afterValues: $employee->fresh()->toArray(),
                description: "Updated profile details for {$employee->full_name} ({$employee->employee_code})."
            );
        });

        return redirect()->route('hr-admin.employees.show', $employee->id)
            ->with('success', 'Employee profile updated successfully.');
    }

    /**
     * Update employee status with reason and account active toggle.
     */
    public function updateStatus(UpdateEmployeeStatusRequest $request, $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validated();

        $beforeStatus = $employee->status;
        $newStatus = EmployeeStatus::from($validated['status']);

        DB::transaction(function () use ($employee, $newStatus, $validated, $beforeStatus) {
            $employee->forceFill([
                'status' => $newStatus,
                'status_change_reason' => $validated['status_change_reason'],
                'status_changed_at' => now(),
            ])->save();

            // Toggle linked user active flag
            if ($employee->user) {
                $employee->user->forceFill([
                    'is_active' => $newStatus === EmployeeStatus::ACTIVE,
                ])->save();
            }

            $this->auditLogger->log(
                action: 'employee.status_changed',
                targetType: 'App\Models\Employee',
                targetId: $employee->id,
                beforeValues: ['status' => $beforeStatus->value ?? (string)$beforeStatus],
                afterValues: ['status' => $newStatus->value, 'reason' => $validated['status_change_reason']],
                description: "Changed status of {$employee->full_name} from " . ($beforeStatus->value ?? (string)$beforeStatus) . " to {$newStatus->value}."
            );
        });

        return back()->with('success', "Employee status updated to {$newStatus->label()}.");
    }

    /**
     * Soft delete / status offboarding guard.
     */
    public function destroy($id): RedirectResponse
    {
        return back()->withErrors([
            'error' => 'Permanent deletion of employee records is prohibited for historical attendance and payroll audit integrity. Please use the Status Offboarding action.',
        ]);
    }
}
