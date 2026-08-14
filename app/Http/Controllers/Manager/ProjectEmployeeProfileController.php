<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectEmployeeProfileController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of project employee resources.
     */
    public function index(Request $request): View
    {
        $query = Employee::with(['user', 'projectProfile', 'teams'])
            ->where('status', \App\Enums\EmployeeStatus::ACTIVE);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($availability = $request->input('availability')) {
            $query->whereHas('projectProfile', function ($q) use ($availability) {
                $q->where('availability_status', $availability);
            });
        }

        if ($skill = $request->input('skill')) {
            $query->whereHas('projectProfile', function ($q) use ($skill) {
                $q->where('skills', 'like', "%\"{$skill}\"%")
                  ->orWhere('skills', 'like', "%{$skill}%");
            });
        }

        $employees = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Employee::where('status', \App\Enums\EmployeeStatus::ACTIVE)->count(),
            'available' => EmployeeProjectProfile::where('availability_status', 'available')->count(),
            'partially_available' => EmployeeProjectProfile::where('availability_status', 'partially_available')->count(),
            'allocated' => EmployeeProjectProfile::where('availability_status', 'allocated')->count(),
            'on_leave' => EmployeeProjectProfile::where('availability_status', 'on_leave')->count(),
        ];

        return view('manager.employees.index', compact('employees', 'stats'));
    }

    /**
     * Display the specified project employee profile (Masking sensitive HR data).
     */
    public function show(Employee $employee): View
    {
        $employee->load([
            'user.projectMemberships.project',
            'projectProfile',
            'teamMemberships.team',
        ]);

        $projectProfile = $employee->projectProfile ?? new EmployeeProjectProfile([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'skills' => [],
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
        ]);

        $primaryTeamMembership = $employee->teamMemberships->firstWhere('is_primary', true);

        return view('manager.employees.show', compact('employee', 'projectProfile', 'primaryTeamMembership'));
    }

    /**
     * Show the form for editing the project employee profile.
     */
    public function edit(Employee $employee): View
    {
        $employee->load(['projectProfile', 'user']);

        $projectProfile = $employee->projectProfile ?? new EmployeeProjectProfile([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'skills' => [],
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
        ]);

        return view('manager.employees.edit', compact('employee', 'projectProfile'));
    }

    /**
     * Update or create the employee project profile.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'skills' => ['nullable'],
            'availability_status' => ['required', 'string', 'in:available,partially_available,allocated,on_leave'],
            'weekly_capacity_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'experience_years' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        // Parse skills into array
        $skills = [];
        if (!empty($validated['skills'])) {
            if (is_array($validated['skills'])) {
                $skills = array_values(array_filter(array_map('trim', $validated['skills'])));
            } else {
                $skills = array_values(array_filter(array_map('trim', explode(',', $validated['skills']))));
            }
        }
        $validated['skills'] = $skills;

        $before = $employee->projectProfile?->toArray();

        $profile = EmployeeProjectProfile::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'user_id' => $employee->user_id,
                'skills' => $validated['skills'],
                'availability_status' => $validated['availability_status'],
                'weekly_capacity_hours' => $validated['weekly_capacity_hours'],
                'experience_years' => $validated['experience_years'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'timezone' => $validated['timezone'] ?? null,
            ]
        );

        $this->auditLogger->log(
            action: 'employee_project_profile.updated',
            targetType: 'Employee',
            targetId: $employee->id,
            beforeValues: $before,
            afterValues: $profile->toArray(),
            description: "Project skills and availability profile updated for employee '{$employee->first_name} {$employee->last_name}'."
        );

        return redirect()->route('manager.employees.profiles.show', $employee)
            ->with('success', "Project profile for {$employee->first_name} updated successfully.");
    }
}
