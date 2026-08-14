<?php

namespace App\Http\Controllers\TeamLead;

use App\Enums\TimesheetStatus;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Timesheet;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectLaborCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamLeadTimesheetApprovalController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectLaborCostService $laborCostService
    ) {}

    /**
     * Display timesheets of squad members for approval.
     */
    public function index(Request $request): View
    {
        $leadId = Auth::id();
        $teamIds = Team::where('team_lead_id', $leadId)->pluck('id');
        $squadEmployeeIds = TeamMember::whereIn('team_id', $teamIds)->pluck('employee_id');

        $query = Timesheet::whereIn('employee_id', $squadEmployeeIds)
            ->with(['employee', 'user', 'entries.project', 'entries.task']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->orderByRaw("FIELD(status, 'submitted', 'returned', 'draft', 'approved', 'rejected')");
        }

        $timesheets = $query->latest('submitted_at')->paginate(15)->withQueryString();

        $stats = [
            'pending' => Timesheet::whereIn('employee_id', $squadEmployeeIds)->where('status', TimesheetStatus::SUBMITTED->value)->count(),
            'approved' => Timesheet::whereIn('employee_id', $squadEmployeeIds)->where('status', TimesheetStatus::APPROVED->value)->count(),
            'rejected' => Timesheet::whereIn('employee_id', $squadEmployeeIds)->where('status', TimesheetStatus::REJECTED->value)->count(),
        ];

        return view('team-lead.timesheets.index', compact('timesheets', 'stats'));
    }

    /**
     * View a squad timesheet.
     */
    public function show(Timesheet $timesheet): View
    {
        $timesheet->load(['employee', 'user', 'approver', 'entries.project', 'entries.task']);
        return view('team-lead.timesheets.show', compact('timesheet'));
    }

    /**
     * Approve squad timesheet.
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

        foreach ($timesheet->entries as $entry) {
            $entryCost = $this->laborCostService->calculateEntryCost($timesheet->employee, (float) $entry->hours);
            $entry->calculated_cost = $entryCost;
            $entry->save();
        }

        $this->auditLogger->logProject(
            action: 'timesheet.approved_by_lead',
            projectId: $timesheet->entries->first()?->project_id ?? 1,
            afterValues: ['timesheet_id' => $timesheet->id, 'approved_by' => Auth::id()],
            description: "Timesheet for squad member {$timesheet->employee->full_name} approved by Team Lead."
        );

        return redirect()->route('team-lead.timesheets.show', $timesheet)
            ->with('success', 'Squad timesheet approved.');
    }

    /**
     * Return squad timesheet.
     */
    public function returnForRevision(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($timesheet->status !== TimesheetStatus::SUBMITTED) {
            return back()->with('error', 'Only submitted timesheets can be returned.');
        }

        $timesheet->status = TimesheetStatus::RETURNED;
        $timesheet->rejection_reason = $validated['rejection_reason'];
        $timesheet->save();

        $this->auditLogger->logProject(
            action: 'timesheet.returned_by_lead',
            projectId: $timesheet->entries->first()?->project_id ?? 1,
            afterValues: ['timesheet_id' => $timesheet->id, 'reason' => $timesheet->rejection_reason],
            description: "Squad timesheet returned to member for revisions."
        );

        return redirect()->route('team-lead.timesheets.show', $timesheet)
            ->with('success', 'Timesheet returned to member for revisions.');
        }
}
