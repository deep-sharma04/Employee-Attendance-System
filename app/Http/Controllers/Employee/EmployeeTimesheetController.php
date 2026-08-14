<?php

namespace App\Http\Controllers\Employee;

use App\Enums\ProjectStatus;
use App\Enums\TimesheetStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectLaborCostService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeTimesheetController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectLaborCostService $laborCostService
    ) {}

    /**
     * Display a listing of employee's timesheets (Task T237).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked to current user.');
        }

        $query = Timesheet::where('employee_id', $employee->id)
            ->with(['approver', 'entries.project', 'entries.task']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $timesheets = $query->latest('start_date')->paginate(12)->withQueryString();

        $stats = [
            'total' => Timesheet::where('employee_id', $employee->id)->count(),
            'draft' => Timesheet::where('employee_id', $employee->id)->where('status', TimesheetStatus::DRAFT->value)->count(),
            'submitted' => Timesheet::where('employee_id', $employee->id)->where('status', TimesheetStatus::SUBMITTED->value)->count(),
            'approved' => Timesheet::where('employee_id', $employee->id)->where('status', TimesheetStatus::APPROVED->value)->count(),
            'rejected' => Timesheet::where('employee_id', $employee->id)->where('status', TimesheetStatus::REJECTED->value)->count(),
            'returned' => Timesheet::where('employee_id', $employee->id)->where('status', TimesheetStatus::RETURNED->value)->count(),
        ];

        return view('employee.timesheets.index', compact('timesheets', 'stats', 'employee'));
    }

    /**
     * Show the form for creating a new weekly timesheet.
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked to current user.');
        }

        // Active projects
        $projects = Project::whereNotIn('status', [ProjectStatus::CANCELLED->value, ProjectStatus::COMPLETED->value])
            ->orderBy('name')
            ->get();

        // Tasks assigned to employee or general
        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('title')
            ->get();

        $defaultStartDate = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $defaultEndDate = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        return view('employee.timesheets.create', compact('projects', 'tasks', 'defaultStartDate', 'defaultEndDate', 'employee'));
    }

    /**
     * Store a newly created timesheet.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked to current user.');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'period_type' => ['required', 'in:weekly,daily'],
            'entries' => ['nullable', 'array'],
            'entries.*.project_id' => ['required', 'exists:projects,id'],
            'entries.*.task_id' => ['nullable', 'exists:tasks,id'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'entries.*.is_billable' => ['nullable', 'boolean'],
            'entries.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $timesheet = Timesheet::create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'period_type' => $validated['period_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => TimesheetStatus::DRAFT->value,
            'total_hours' => 0.00,
            'created_by' => $user->id,
        ]);

        if (!empty($validated['entries'])) {
            foreach ($validated['entries'] as $entryData) {
                $cost = $this->laborCostService->calculateEntryCost($employee, (float) $entryData['hours']);
                TimesheetEntry::create([
                    'timesheet_id' => $timesheet->id,
                    'project_id' => $entryData['project_id'],
                    'task_id' => $entryData['task_id'] ?? null,
                    'entry_date' => $entryData['entry_date'],
                    'hours' => $entryData['hours'],
                    'is_billable' => !empty($entryData['is_billable']),
                    'description' => $entryData['description'] ?? null,
                    'calculated_cost' => $cost,
                ]);
            }
        }

        $timesheet->recalculateTotalHours();

        $this->auditLogger->logProject(
            action: 'timesheet.created',
            projectId: $validated['entries'][0]['project_id'] ?? 1,
            afterValues: ['timesheet_id' => $timesheet->id, 'start_date' => $timesheet->start_date->toDateString(), 'total_hours' => $timesheet->total_hours],
            description: "Timesheet period {$timesheet->start_date->format('M d')} to {$timesheet->end_date->format('M d, Y')} created in Draft."
        );

        return redirect()->route('employee.timesheets.show', $timesheet)
            ->with('success', 'Timesheet draft created successfully.');
    }

    /**
     * Display timesheet details (Task T237).
     */
    public function show(Timesheet $timesheet): View
    {
        $user = Auth::user();
        if ($timesheet->user_id !== $user->id && $user->role->value === 'employee') {
            abort(403, 'Unauthorized to view this timesheet.');
        }

        $timesheet->load(['employee', 'approver', 'entries.project', 'entries.task']);

        $projects = Project::whereNotIn('status', [ProjectStatus::CANCELLED->value, ProjectStatus::COMPLETED->value])
            ->orderBy('name')
            ->get();
        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('title')
            ->get();

        return view('employee.timesheets.show', compact('timesheet', 'projects', 'tasks'));
    }

    /**
     * Add an individual entry to draft/returned timesheet.
     */
    public function storeEntry(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $user = Auth::user();
        if ($timesheet->user_id !== $user->id) {
            abort(403);
        }

        if (!$timesheet->isEditable()) {
            return back()->with('error', 'Cannot add entries to a locked or submitted timesheet.');
        }

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'entry_date' => ['required', 'date', 'after_or_equal:' . $timesheet->start_date->toDateString(), 'before_or_equal:' . $timesheet->end_date->toDateString()],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'is_billable' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = $timesheet->employee;
        $cost = $this->laborCostService->calculateEntryCost($employee, (float) $validated['hours']);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $validated['project_id'],
            'task_id' => $validated['task_id'] ?? null,
            'entry_date' => $validated['entry_date'],
            'hours' => $validated['hours'],
            'is_billable' => $request->boolean('is_billable'),
            'description' => $validated['description'] ?? null,
            'calculated_cost' => $cost,
        ]);

        $timesheet->recalculateTotalHours();

        return back()->with('success', 'Work log entry added.');
    }

    /**
     * Remove an individual entry from draft/returned timesheet.
     */
    public function destroyEntry(Timesheet $timesheet, TimesheetEntry $entry): RedirectResponse
    {
        $user = Auth::user();
        if ($timesheet->user_id !== $user->id || $entry->timesheet_id !== $timesheet->id) {
            abort(403);
        }

        if (!$timesheet->isEditable()) {
            return back()->with('error', 'Cannot modify a locked or submitted timesheet.');
        }

        $entry->delete();
        $timesheet->recalculateTotalHours();

        return back()->with('success', 'Work log entry removed.');
    }

    /**
     * Submit timesheet for approval (Task T238).
     */
    public function submit(Timesheet $timesheet): RedirectResponse
    {
        $user = Auth::user();
        if ($timesheet->user_id !== $user->id) {
            abort(403);
        }

        if (!$timesheet->isEditable()) {
            return back()->with('error', 'Only draft or returned timesheets can be submitted.');
        }

        if ($timesheet->entries()->count() === 0) {
            return back()->with('error', 'Cannot submit an empty timesheet. Please log at least one work entry.');
        }

        $timesheet->status = TimesheetStatus::SUBMITTED;
        $timesheet->submitted_at = now();
        $timesheet->rejection_reason = null;
        $timesheet->save();

        $this->auditLogger->logProject(
            action: 'timesheet.submitted',
            projectId: $timesheet->entries()->first()?->project_id ?? 1,
            afterValues: [
                'timesheet_id' => $timesheet->id,
                'status' => $timesheet->status->value,
                'total_hours' => $timesheet->total_hours,
            ],
            description: "Timesheet ({$timesheet->start_date->format('M d')} - {$timesheet->end_date->format('M d, Y')}) submitted for manager/lead approval."
        );

        return back()->with('success', 'Timesheet submitted successfully for review.');
    }

    /**
     * Delete draft timesheet.
     */
    public function destroy(Timesheet $timesheet): RedirectResponse
    {
        $user = Auth::user();
        if ($timesheet->user_id !== $user->id) {
            abort(403);
        }

        if (!$timesheet->isEditable()) {
            return back()->with('error', 'Cannot delete submitted or approved timesheets.');
        }

        $timesheet->delete();

        return redirect()->route('employee.timesheets.index')
            ->with('success', 'Timesheet draft deleted.');
    }
}
