<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Models\Shift;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShiftManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of configured shifts and assigned employee counts.
     */
    public function index(): View
    {
        $shifts = Shift::withCount('employees')->orderBy('name')->get();

        return view('hr-admin.shifts.index', [
            'shifts' => $shifts,
        ]);
    }

    /**
     * Show the form for creating a new shift.
     */
    public function create(): View
    {
        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('hr-admin.shifts.create', [
            'allDays' => $allDays,
        ]);
    }

    /**
     * Store a newly created shift in database with audit trail.
     */
    public function store(StoreShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        // Normalize times with seconds if needed
        if (strlen($validated['start_time']) === 5) {
            $validated['start_time'] .= ':00';
        }
        if (strlen($validated['end_time']) === 5) {
            $validated['end_time'] .= ':00';
        }

        $shift = Shift::create($validated);

        $this->auditLogger->log(
            action: 'shift.created',
            targetType: 'App\Models\Shift',
            targetId: $shift->id,
            afterValues: $shift->toArray(),
            description: "Created shift schedule {$shift->name} ({$shift->code})."
        );

        return redirect()->route('hr-admin.shifts.index')
            ->with('success', "Shift schedule '{$shift->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified shift.
     */
    public function edit($id): View
    {
        $shift = Shift::findOrFail($id);
        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('hr-admin.shifts.edit', [
            'shift' => $shift,
            'allDays' => $allDays,
        ]);
    }

    /**
     * Update the specified shift in storage.
     */
    public function update(UpdateShiftRequest $request, $id): RedirectResponse
    {
        $shift = Shift::findOrFail($id);
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        if (strlen($validated['start_time']) === 5) {
            $validated['start_time'] .= ':00';
        }
        if (strlen($validated['end_time']) === 5) {
            $validated['end_time'] .= ':00';
        }

        $beforeValues = $shift->toArray();
        $shift->update($validated);

        $this->auditLogger->log(
            action: 'shift.updated',
            targetType: 'App\Models\Shift',
            targetId: $shift->id,
            beforeValues: $beforeValues,
            afterValues: $shift->fresh()->toArray(),
            description: "Updated configuration for shift {$shift->name} ({$shift->code})."
        );

        return redirect()->route('hr-admin.shifts.index')
            ->with('success', "Shift '{$shift->name}' updated successfully.");
    }

    /**
     * Toggle shift active/inactive status without deleting historical assignments.
     */
    public function toggleStatus($id): RedirectResponse
    {
        $shift = Shift::findOrFail($id);
        $newStatus = !$shift->is_active;

        $shift->forceFill(['is_active' => $newStatus])->save();

        $this->auditLogger->log(
            action: 'shift.status_toggled',
            targetType: 'App\Models\Shift',
            targetId: $shift->id,
            afterValues: ['is_active' => $newStatus],
            description: "Toggled status of shift {$shift->name} to " . ($newStatus ? 'Active' : 'Inactive') . "."
        );

        $statusLabel = $newStatus ? 'activated' : 'deactivated';
        return back()->with('success', "Shift '{$shift->name}' {$statusLabel} successfully.");
    }
}
