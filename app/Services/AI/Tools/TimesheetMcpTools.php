<?php

namespace App\Services\AI\Tools;

use App\Enums\TimesheetStatus;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectLaborCostService;
use Illuminate\Validation\ValidationException;

class TimesheetMcpTools
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectLaborCostService $laborCostService
    ) {}

    /**
     * T282: timesheet.search
     */
    public function search(User $user, array $args): array
    {
        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Clients cannot view internal timesheets.');
        }

        $query = Timesheet::with(['employee', 'entries.project']);

        // Employee scope isolation: only see their own timesheets
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('entries.project', fn ($p) => $p->where('manager_id', $user->id));
            });
        }

        if (!empty($args['employee_id'])) {
            $query->where('employee_id', (int) $args['employee_id']);
        }

        if (!empty($args['project_id'])) {
            $query->whereHas('entries', fn ($e) => $e->where('project_id', (int) $args['project_id']));
        }

        if (!empty($args['status'])) {
            $query->where('status', (string) $args['status']);
        }

        if (!empty($args['start_date'])) {
            $query->where('start_date', '>=', (string) $args['start_date']);
        }

        if (!empty($args['end_date'])) {
            $query->where('end_date', '<=', (string) $args['end_date']);
        }

        $limit = min(50, max(1, (int) ($args['limit'] ?? 15)));
        $timesheets = $query->latest('start_date')->take($limit)->get();

        $sanitized = $timesheets->map(fn (Timesheet $t) => [
            'id' => $t->id,
            'employee_id' => $t->employee_id,
            'employee_name' => $t->employee ? ($t->employee->first_name . ' ' . $t->employee->last_name) : 'Unknown',
            'period_type' => $t->period_type,
            'start_date' => $t->start_date?->toDateString(),
            'end_date' => $t->end_date?->toDateString(),
            'total_hours' => (float) $t->total_hours,
            'status' => $t->status->value,
            'submitted_at' => $t->submitted_at?->toIso8601String(),
            'approved_at' => $t->approved_at?->toIso8601String(),
        ])->all();

        return [
            'timesheets' => $sanitized,
            'count' => count($sanitized),
        ];
    }

    /**
     * T282: timesheet.create
     */
    public function create(User $user, array $args): array
    {
        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Clients cannot create timesheets.');
        }

        $employee = $user->employee;
        if (!$employee && !empty($args['employee_id']) && ($user->isSuperAdmin() || $user->isManager())) {
            $employee = Employee::find((int) $args['employee_id']);
        }

        if (!$employee) {
            throw new \RuntimeException('No active employee record found for timesheet creation.');
        }

        $validator = validator($args, [
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

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $timesheet = Timesheet::create([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'period_type' => $validated['period_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => TimesheetStatus::DRAFT->value,
            'total_hours' => 0.00,
            'created_by' => $user->id,
        ]);

        if (!empty($validated['entries'])) {
            foreach ($validated['entries'] as $entry) {
                $cost = $this->laborCostService->calculateEntryCost($employee, (float) $entry['hours']);
                TimesheetEntry::create([
                    'timesheet_id' => $timesheet->id,
                    'project_id' => $entry['project_id'],
                    'task_id' => $entry['task_id'] ?? null,
                    'entry_date' => $entry['entry_date'],
                    'hours' => $entry['hours'],
                    'is_billable' => !empty($entry['is_billable']),
                    'description' => $entry['description'] ?? null,
                    'calculated_cost' => $cost,
                ]);
            }
        }

        $timesheet->recalculateTotalHours();

        $firstProjectId = $validated['entries'][0]['project_id'] ?? 1;
        $this->auditLogger->logProject(
            action: 'timesheet.created',
            projectId: $firstProjectId,
            afterValues: ['timesheet_id' => $timesheet->id, 'start_date' => $timesheet->start_date->toDateString(), 'total_hours' => $timesheet->total_hours],
            description: "Timesheet ({$timesheet->start_date->toDateString()} - {$timesheet->end_date->toDateString()}) created in Draft via MCP by {$user->name}."
        );

        return [
            'timesheet_id' => $timesheet->id,
            'start_date' => $timesheet->start_date->toDateString(),
            'end_date' => $timesheet->end_date->toDateString(),
            'total_hours' => (float) $timesheet->total_hours,
            'status' => $timesheet->status->value,
            'message' => 'Timesheet draft created successfully.',
        ];
    }
}
