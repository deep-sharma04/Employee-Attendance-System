<?php

namespace App\Http\Controllers\Manager;

use App\Enums\TimesheetStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\Timesheet;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectLaborCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TimesheetApprovalController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectLaborCostService $laborCostService
    ) {}

    /**
     * Display the timesheet approval queue (Task T240).
     */
    public function index(Request $request): View
    {
        $query = Timesheet::with(['employee', 'user', 'approver', 'entries.project', 'entries.task']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            // Default show pending submitted timesheets at top
            $query->orderByRaw("FIELD(status, 'submitted', 'returned', 'draft', 'approved', 'rejected')");
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($projectId = $request->input('project_id')) {
            $query->whereHas('entries', fn ($eq) => $eq->where('project_id', $projectId));
        }

        $timesheets = $query->latest('submitted_at')->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'pending' => Timesheet::where('status', TimesheetStatus::SUBMITTED->value)->count(),
            'approved' => Timesheet::where('status', TimesheetStatus::APPROVED->value)->count(),
            'rejected' => Timesheet::where('status', TimesheetStatus::REJECTED->value)->count(),
            'returned' => Timesheet::where('status', TimesheetStatus::RETURNED->value)->count(),
        ];

        $employees = Employee::where('status', \App\Enums\EmployeeStatus::ACTIVE)->orderBy('first_name')->get();
        $projects = Project::orderBy('name')->get();

        return view('manager.timesheets.index', compact('timesheets', 'stats', 'employees', 'projects'));
    }

    /**
     * Review a specific timesheet submission (Task T240).
     */
    public function show(Timesheet $timesheet): View
    {
        $timesheet->load(['employee', 'user', 'approver', 'entries.project', 'entries.task']);

        $totalLaborCost = $timesheet->entries->sum('calculated_cost');

        return view('manager.timesheets.show', compact('timesheet', 'totalLaborCost'));
    }

    /**
     * Approve a submitted timesheet (Task T239 & T241).
     */
    public function approve(Request $request, Timesheet $timesheet): RedirectResponse
    {
        if ($timesheet->status !== TimesheetStatus::SUBMITTED) {
            return back()->with('error', 'Only submitted timesheets can be approved.');
        }

        $timesheet->status = TimesheetStatus::APPROVED;
        $timesheet->approved_by = Auth::id();
        $timesheet->approved_at = now();
        $timesheet->save();

        // Lock and ensure labor cost calculation on every entry (Task T241)
        foreach ($timesheet->entries as $entry) {
            $entryCost = $this->laborCostService->calculateEntryCost($timesheet->employee, (float) $entry->hours);
            $entry->calculated_cost = $entryCost;
            $entry->save();

            // Update actual hours on associated task if linked
            if ($entry->task_id) {
                $task = Task::find($entry->task_id);
                if ($task) {
                    $task->actual_hours = (float) $task->actual_hours + (float) $entry->hours;
                    $task->save();
                }
            }
        }

        $this->auditLogger->logProject(
            action: 'timesheet.approved',
            projectId: $timesheet->entries->first()?->project_id ?? 1,
            afterValues: [
                'timesheet_id' => $timesheet->id,
                'status' => $timesheet->status->value,
                'total_hours' => $timesheet->total_hours,
                'approved_by' => Auth::id(),
            ],
            description: "Timesheet for {$timesheet->employee->full_name} ({$timesheet->start_date->format('M d')} - {$timesheet->end_date->format('M d, Y')}) approved."
        );

        return redirect()->route('manager.timesheets.show', $timesheet)
            ->with('success', "Timesheet approved successfully.");
    }

    /**
     * Reject a submitted timesheet (Task T239).
     */
    public function reject(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($timesheet->status !== TimesheetStatus::SUBMITTED) {
            return back()->with('error', 'Only submitted timesheets can be rejected.');
        }

        $timesheet->status = TimesheetStatus::REJECTED;
        $timesheet->approved_by = Auth::id();
        $timesheet->approved_at = now();
        $timesheet->rejection_reason = $validated['rejection_reason'];
        $timesheet->save();

        $this->auditLogger->logProject(
            action: 'timesheet.rejected',
            projectId: $timesheet->entries->first()?->project_id ?? 1,
            afterValues: [
                'timesheet_id' => $timesheet->id,
                'status' => $timesheet->status->value,
                'rejection_reason' => $timesheet->rejection_reason,
            ],
            description: "Timesheet for {$timesheet->employee->full_name} rejected. Reason: {$validated['rejection_reason']}"
        );

        return redirect()->route('manager.timesheets.show', $timesheet)
            ->with('success', 'Timesheet rejected.');
    }

    /**
     * Return a submitted timesheet for revision (Task T239).
     */
    public function returnForRevision(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($timesheet->status !== TimesheetStatus::SUBMITTED) {
            return back()->with('error', 'Only submitted timesheets can be returned for revision.');
        }

        $timesheet->status = TimesheetStatus::RETURNED;
        $timesheet->rejection_reason = $validated['rejection_reason'];
        $timesheet->save();

        $this->auditLogger->logProject(
            action: 'timesheet.returned',
            projectId: $timesheet->entries->first()?->project_id ?? 1,
            afterValues: [
                'timesheet_id' => $timesheet->id,
                'status' => $timesheet->status->value,
                'return_reason' => $timesheet->rejection_reason,
            ],
            description: "Timesheet for {$timesheet->employee->full_name} returned for employee revision."
        );

        return redirect()->route('manager.timesheets.show', $timesheet)
            ->with('success', 'Timesheet returned to employee for revisions.');
    }
}
