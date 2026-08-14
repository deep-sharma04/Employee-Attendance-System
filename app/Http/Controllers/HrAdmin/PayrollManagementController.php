<?php

namespace App\Http\Controllers\HrAdmin;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\CreateRevisionRequest;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Http\Requests\Payroll\UpdatePaymentStatusRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\Audit\AuditLoggerService;
use App\Services\Notification\NotificationService;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\PayslipGenerationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PayrollManagementController extends Controller
{
    public function __construct(
        protected PayrollGenerationService $payrollGenerationService,
        protected PayslipGenerationService $payslipGenerationService,
        protected AuditLoggerService $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    // ... (Keep your existing index, create, generate, show, markReviewed, approve methods exactly as they are) ...

    public function index(Request $request): View
    {
        $selectedYear = (int) ($request->input('year') ?: date('Y'));
        $selectedMonth = (int) ($request->input('month') ?: date('n'));

        $query = Payroll::with(['employee.user', 'generator', 'approver', 'finalizer', 'items'])
            ->where('payroll_year', $selectedYear);

        if ($request->filled('month')) {
            $query->where('payroll_month', $selectedMonth);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $payrolls = $query->latest('payroll_year')->latest('payroll_month')->latest('id')->paginate(15)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        $allPayrollsThisPeriod = Payroll::where('payroll_year', $selectedYear)
            ->when($request->filled('month'), fn($q) => $q->where('payroll_month', $selectedMonth))
            ->get();

        $stats = [
            'total_payrolls' => $allPayrollsThisPeriod->count(),
            'total_gross' => (float) $allPayrollsThisPeriod->sum('monthly_salary'),
            'total_lop_deductions' => (float) $allPayrollsThisPeriod->sum('lop_deduction_amount'),
            'total_net_pay' => (float) $allPayrollsThisPeriod->sum('net_salary'),
            'draft_count' => $allPayrollsThisPeriod->where('status', PayrollStatus::DRAFT)->count(),
            'reviewed_count' => $allPayrollsThisPeriod->where('status', PayrollStatus::REVIEWED)->count(),
            'approved_count' => $allPayrollsThisPeriod->where('status', PayrollStatus::APPROVED)->count(),
            'finalized_count' => $allPayrollsThisPeriod->where('status', PayrollStatus::FINALIZED)->count(),
        ];

        return view('hr-admin.payroll.index', compact(
            'payrolls', 'employees', 'stats', 'selectedYear', 'selectedMonth'
        ));
    }

    public function create(): View
    {
        $activeEmployees = Employee::where('status', 'active')->orderBy('first_name')->get();
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        return view('hr-admin.payroll.create', compact('activeEmployees', 'currentYear', 'currentMonth'));
    }

    public function generate(GeneratePayrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $employeeId = $validated['employee_id'] ?? null;

        if ($employeeId) {
            try {
                $payroll = $this->payrollGenerationService->generateForEmployee((int) $employeeId, $year, $month, Auth::id());
                return redirect()->route('hr-admin.payroll.show', $payroll->id)
                    ->with('success', "Payroll generated successfully for {$payroll->employee->first_name} {$payroll->employee->last_name} ({$year}-{$month}).");
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $result = $this->payrollGenerationService->generateBatch($year, $month, Auth::id());

        $msg = "Batch generation complete: {$result['total_generated']} generated.";
        if ($result['skipped'] > 0) {
            $msg .= " ({$result['skipped']} skipped or already finalized).";
        }

        return redirect()->route('hr-admin.payroll.index', ['year' => $year, 'month' => $month])
            ->with('success', $msg);
    }

    public function show(int $id): View
    {
        $payroll = Payroll::with(['employee.user', 'employee.shift', 'items', 'generator', 'approver', 'finalizer', 'payslip'])
            ->findOrFail($id);

        $revisions = Payroll::where('employee_id', $payroll->employee_id)
            ->where('payroll_year', $payroll->payroll_year)
            ->where('payroll_month', $payroll->payroll_month)
            ->where('id', '!=', $payroll->id)
            ->orderBy('revision_number')
            ->get();

        return view('hr-admin.payroll.show', compact('payroll', 'revisions'));
    }

    public function markReviewed(int $id): RedirectResponse
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status === PayrollStatus::FINALIZED) {
            return back()->with('error', 'Cannot modify a finalized payroll without an authorized revision.');
        }

        $beforeValues = ['status' => $payroll->status->value];
        $payroll->update(['status' => PayrollStatus::REVIEWED]);

        $this->auditLogger->log(
            action: 'payroll.reviewed',
            targetType: 'Payroll',
            targetId: $payroll->id,
            beforeValues: $beforeValues,
            afterValues: ['status' => 'reviewed'],
            description: "Marked payroll for Employee ID {$payroll->employee_id} ({$payroll->payroll_year}-{$payroll->payroll_month}) as Reviewed"
        );

        return back()->with('success', 'Payroll marked as Reviewed and prepared for Super Admin approval.');
    }

    public function approve(int $id): RedirectResponse
    {
        $user = Auth::user();
        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $isSuperAdmin = $userRole === 'super_admin';

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators are authorized to approve payroll.');
        }

        $payroll = Payroll::findOrFail($id);
        $beforeValues = ['status' => $payroll->status->value];

        $payroll->update([
            'status' => PayrollStatus::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'payroll.approved',
            targetType: 'Payroll',
            targetId: $payroll->id,
            beforeValues: $beforeValues,
            afterValues: [
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now()->toDateTimeString(),
            ],
            description: "Super Admin approved payroll for Employee ID {$payroll->employee_id} ({$payroll->payroll_year}-{$payroll->payroll_month})"
        );

        return back()->with('success', 'Payroll approved successfully.');
    }

    /**
     * Super Admin finalizes and locks the approved payroll.
     */
    public function finalize(int $id): RedirectResponse
    {
        $user = Auth::user();
        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $isSuperAdmin = $userRole === 'super_admin';

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators are authorized to finalize and lock payroll.');
        }

        $payroll = Payroll::findOrFail($id);
        $beforeValues = ['status' => $payroll->status->value];

        $payroll->update([
            'status' => PayrollStatus::FINALIZED,
            'finalized_by' => $user->id,
            'finalized_at' => now(),
        ]);

        // T132 & T133: Automatically generate the linked Payslip PDF upon finalization
        $this->payslipGenerationService->generateForPayroll($payroll);

        // T167: In-App Notification for employee
        $this->notificationService->notifyPayslipAvailable($payroll);

        $this->auditLogger->log(
            action: 'payroll.finalized',
            targetType: 'Payroll',
            targetId: $payroll->id,
            beforeValues: $beforeValues,
            afterValues: [
                'status' => 'finalized',
                'finalized_by' => $user->id,
                'finalized_at' => now()->toDateTimeString(),
            ],
            description: "Super Admin finalized and locked payroll for Employee ID {$payroll->employee_id} ({$payroll->payroll_year}-{$payroll->payroll_month})"
        );

        return back()->with('success', 'Payroll finalized and locked. Payslip is now officially available.');
    }

    public function createRevision(CreateRevisionRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $newPayroll = $this->payrollGenerationService->createRevision(
            $id,
            $validated['revision_reason'],
            Auth::id()
        );

        return redirect()->route('hr-admin.payroll.show', $newPayroll->id)
            ->with('success', "Revision #{$newPayroll->revision_number} initialized successfully for adjustment.");
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $payroll = Payroll::findOrFail($id);
        $beforeStatus = $payroll->payment_status instanceof PaymentStatus ? $payroll->payment_status->value : (string) $payroll->payment_status;
        $newStatus = $validated['payment_status'];

        $payroll->update([
            'payment_status' => $newStatus,
        ]);

        $this->auditLogger->log(
            action: 'payroll.payment_status_updated',
            targetType: 'Payroll',
            targetId: $payroll->id,
            beforeValues: ['payment_status' => $beforeStatus],
            afterValues: ['payment_status' => $newStatus],
            description: "Updated payment status for Payroll ID {$payroll->id} from {$beforeStatus} to {$newStatus}"
        );

        return back()->with('success', "Payment status updated to " . ucfirst($newStatus) . ".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status === PayrollStatus::FINALIZED) {
            return back()->with('error', 'Finalized payroll records are locked and cannot be deleted. Use revisions instead.');
        }

        $beforeValues = $payroll->toArray();
        $payroll->delete();

        $this->auditLogger->log(
            action: 'payroll.deleted',
            targetType: 'Payroll',
            targetId: $id,
            beforeValues: $beforeValues,
            afterValues: null,
            description: "Deleted draft payroll for Employee ID {$beforeValues['employee_id']}"
        );

        return redirect()->route('hr-admin.payroll.index')
            ->with('success', 'Draft payroll record deleted successfully.');
    }

    // T135: Build Payslip Access for HR/Super Admin
    public function viewPayslip(int $id)
    {
        $payroll = Payroll::with('payslip')->findOrFail($id);

        if (!$payroll->payslip || !Storage::disk('local')->exists($payroll->payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        return Storage::disk('local')->response($payroll->payslip->file_path);
    }

    public function downloadPayslip(int $id)
    {
        $payroll = Payroll::with('payslip')->findOrFail($id);

        if (!$payroll->payslip || !Storage::disk('local')->exists($payroll->payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        return Storage::disk('local')->download($payroll->payslip->file_path, $payroll->payslip->payslip_number . '.pdf');
    }
}