<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\EmployeeTaskController;
use App\Http\Controllers\Employee\EmployeeTimesheetController;
use App\Http\Controllers\Employee\LeaveRequestController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\PunchAttendanceController;
use App\Http\Controllers\HrAdmin\AttendanceManagementController;
use App\Http\Controllers\HrAdmin\DocumentManagementController;
use App\Http\Controllers\HrAdmin\EmployeeManagementController;
use App\Http\Controllers\HrAdmin\HolidayCalendarController;
use App\Http\Controllers\HrAdmin\HrDashboardController;
use App\Http\Controllers\HrAdmin\IpAllowlistController;
use App\Http\Controllers\HrAdmin\LeaveManagementController;
use App\Http\Controllers\HrAdmin\PayrollManagementController;
use App\Http\Controllers\HrAdmin\ReportController;
use App\Http\Controllers\HrAdmin\ShiftManagementController;
use App\Http\Controllers\ClientPortal\ClientPortalDashboardController;
use App\Http\Controllers\Knowledge\KnowledgeSearchController;
use App\Http\Controllers\Reporting\ProjectReportController;
use App\Http\Controllers\Manager\ClientManagementController;
use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\ProjectDocumentController;
use App\Http\Controllers\Manager\ProjectEmployeeProfileController;
use App\Http\Controllers\Manager\ProjectManagementController;
use App\Http\Controllers\Manager\TaskManagementController;
use App\Http\Controllers\Manager\TeamManagementController;
use App\Http\Controllers\Manager\TimesheetApprovalController;
use App\Http\Controllers\SuperAdmin\AuditLogViewerController;
use App\Http\Controllers\SuperAdmin\CompanySettingsController;
use App\Http\Controllers\SuperAdmin\HrAdminManagementController;
use App\Http\Controllers\SuperAdmin\ProjectHealthSettingController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\TeamLead\TeamLeadDashboardController;
use App\Http\Controllers\TeamLead\TeamLeadTaskController;
use App\Http\Controllers\TeamLead\TeamLeadTeamController;
use App\Http\Controllers\TeamLead\TeamLeadTimesheetApprovalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Base Route Groups & Role Prefixes
|--------------------------------------------------------------------------
*/

// Root URL: Redirect to appropriate role dashboard or login
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        return match ($role) {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'hr_admin' => redirect()->route('hr-admin.dashboard'),
            'employee' => redirect()->route('employee.dashboard'),
            'manager' => redirect()->route('manager.dashboard'),
            'team_lead' => redirect()->route('team-lead.dashboard'),
            'client' => redirect()->route('client-portal.dashboard'),
            default => redirect()->route('login'),
        };
    }

    return redirect()->route('login');
})->name('home');

// Authentication & Public Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
    Route::get('/password/forgot', [PasswordController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/password/forgot', [PasswordController::class, 'sendResetLink'])->name('password.forgot.post')->middleware('throttle:5,1');
    Route::get('/password/reset/{token}', [PasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordController::class, 'resetPassword'])->name('password.reset.post')->middleware('throttle:5,1');
});

// Authenticated Global Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'updatePassword'])->name('password.change.post')->middleware('throttle:6,1');

    // Notifications (Phase 16: T165 - T168 & Phase 29: T262 - T266)
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    Route::get('/notifications/dispatches', [\App\Http\Controllers\NotificationController::class, 'dispatches'])->name('notifications.dispatches');
    Route::match(['get', 'post'], '/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::match(['get', 'post'], '/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Super Admin Protected Area
|--------------------------------------------------------------------------
*/
Route::prefix('super-admin')
    ->name('super-admin.')
    ->middleware(['auth', 'active', 'role:super_admin'])
    ->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // HR Admin Management (Phase 14: T157 - T160)
        Route::get('/hr-admins', [HrAdminManagementController::class, 'index'])->name('hr-admins.index');
        Route::get('/hr-admins/create', [HrAdminManagementController::class, 'create'])->name('hr-admins.create');
        Route::post('/hr-admins', [HrAdminManagementController::class, 'store'])->name('hr-admins.store');
        Route::get('/hr-admins/{id}/edit', [HrAdminManagementController::class, 'edit'])->name('hr-admins.edit');
        Route::put('/hr-admins/{id}', [HrAdminManagementController::class, 'update'])->name('hr-admins.update');
        Route::post('/hr-admins/{id}/toggle-status', [HrAdminManagementController::class, 'toggleStatus'])->name('hr-admins.toggle-status');

        // Company & Payslip Settings
        Route::get('/settings', [CompanySettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [CompanySettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/mail-test', [CompanySettingsController::class, 'sendTestEmail'])->name('settings.mail-test');

        // Project Health Engine Threshold Settings (Phase 23: Task T224)
        Route::get('/settings/project-health', [ProjectHealthSettingController::class, 'index'])->name('settings.project-health');
        Route::post('/settings/project-health', [ProjectHealthSettingController::class, 'update'])->name('settings.project-health.update');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogViewerController::class, 'index'])->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| HR Admin & Super Admin Management Area
|--------------------------------------------------------------------------
*/
Route::prefix('hr-admin')
    ->name('hr-admin.')
    ->middleware(['auth', 'active', 'role:super_admin,hr_admin'])
    ->group(function () {
        Route::get('/dashboard', [HrDashboardController::class, 'index'])->name('dashboard');

        // Employees
        Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeManagementController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeManagementController::class, 'store'])->name('employees.store');
        Route::get('/employees/{id}', [EmployeeManagementController::class, 'show'])->name('employees.show');
        Route::get('/employees/{id}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{id}', [EmployeeManagementController::class, 'update'])->name('employees.update');
        Route::post('/employees/{id}/status', [EmployeeManagementController::class, 'updateStatus'])->name('employees.update-status');
        
        // Shifts & IP Allowlist & Holidays
        Route::get('/shifts', [ShiftManagementController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/create', [ShiftManagementController::class, 'create'])->name('shifts.create');
        Route::post('/shifts', [ShiftManagementController::class, 'store'])->name('shifts.store');
        Route::get('/shifts/{id}/edit', [ShiftManagementController::class, 'edit'])->name('shifts.edit');
        Route::put('/shifts/{id}', [ShiftManagementController::class, 'update'])->name('shifts.update');
        Route::post('/shifts/{id}/toggle-status', [ShiftManagementController::class, 'toggleStatus'])->name('shifts.toggle-status');

        Route::get('/ip-allowlists', [IpAllowlistController::class, 'index'])->name('ip-allowlists.index');
        Route::post('/ip-allowlists', [IpAllowlistController::class, 'store'])->name('ip-allowlists.store');
        Route::put('/ip-allowlists/{id}', [IpAllowlistController::class, 'update'])->name('ip-allowlists.update');
        Route::post('/ip-allowlists/{id}/toggle-status', [IpAllowlistController::class, 'toggleStatus'])->name('ip-allowlists.toggle-status');
        Route::delete('/ip-allowlists/{id}', [IpAllowlistController::class, 'destroy'])->name('ip-allowlists.destroy');

        Route::get('/holidays', [HolidayCalendarController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [HolidayCalendarController::class, 'store'])->name('holidays.store');
        Route::put('/holidays/{id}', [HolidayCalendarController::class, 'update'])->name('holidays.update');
        Route::delete('/holidays/{id}', [HolidayCalendarController::class, 'destroy'])->name('holidays.destroy');

        // Attendance & Correction
        Route::get('/attendance', [AttendanceManagementController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/correct/{id}', [AttendanceManagementController::class, 'createCorrection'])->name('attendance.correct');
        Route::post('/attendance/correct/{id}', [AttendanceManagementController::class, 'storeCorrection'])->name('attendance.store-correction');
        Route::post('/attendance/manual', [AttendanceManagementController::class, 'storeManualEntry'])->name('attendance.store-manual');

        // Leaves & Documents & Payroll
        Route::get('/leaves', [LeaveManagementController::class, 'index'])->name('leaves.index');
        Route::post('/leaves/{id}/approve', [LeaveManagementController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{id}/reject', [LeaveManagementController::class, 'reject'])->name('leaves.reject');
        Route::get('/leaves/types', [LeaveManagementController::class, 'types'])->name('leaves.types');
        Route::post('/leaves/types', [LeaveManagementController::class, 'storeType'])->name('leaves.types.store');
        Route::put('/leaves/types/{id}', [LeaveManagementController::class, 'updateType'])->name('leaves.types.update');
        Route::post('/leaves/allocation', [LeaveManagementController::class, 'storeAllocation'])->name('leaves.allocation.store');

        // Documents Management & Verification
        Route::get('/documents', [DocumentManagementController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [DocumentManagementController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentManagementController::class, 'store'])->name('documents.store');
        Route::get('/documents/{id}/view', [DocumentManagementController::class, 'viewFile'])->name('documents.view');
        Route::get('/documents/{id}/download', [DocumentManagementController::class, 'download'])->name('documents.download');
        Route::post('/documents/{id}/verify', [DocumentManagementController::class, 'verify'])->name('documents.verify');
        Route::post('/documents/{id}/reject', [DocumentManagementController::class, 'reject'])->name('documents.reject');
        Route::delete('/documents/{id}', [DocumentManagementController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/types', [DocumentManagementController::class, 'types'])->name('documents.types');
        Route::post('/documents/types', [DocumentManagementController::class, 'storeType'])->name('documents.types.store');
        Route::put('/documents/types/{id}', [DocumentManagementController::class, 'updateType'])->name('documents.types.update');
        Route::delete('/documents/types/{id}', [DocumentManagementController::class, 'destroyType'])->name('documents.types.destroy');

        // Payroll Operations & Finalization Workflow
        Route::get('/payroll', [PayrollManagementController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollManagementController::class, 'create'])->name('payroll.create');
        Route::post('/payroll/generate', [PayrollManagementController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/{id}', [PayrollManagementController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{id}/review', [PayrollManagementController::class, 'markReviewed'])->name('payroll.review');
        Route::post('/payroll/{id}/approve', [PayrollManagementController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/{id}/finalize', [PayrollManagementController::class, 'finalize'])->name('payroll.finalize');
        Route::post('/payroll/{id}/revision', [PayrollManagementController::class, 'createRevision'])->name('payroll.revision');
        Route::post('/payroll/{id}/payment-status', [PayrollManagementController::class, 'updatePaymentStatus'])->name('payroll.payment-status');
        Route::delete('/payroll/{id}', [PayrollManagementController::class, 'destroy'])->name('payroll.destroy');
        Route::get('/payroll/{id}/payslip/view', [PayrollManagementController::class, 'viewPayslip'])->name('payroll.payslip.view');
        Route::get('/payroll/{id}/payslip/download', [PayrollManagementController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        // Reports & Data Exports (T140 - T144)
        Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/attendance/export', [ReportController::class, 'exportAttendanceCsv'])->name('reports.attendance.export');
        Route::get('/reports/leave', [ReportController::class, 'leave'])->name('reports.leave');
        Route::get('/reports/leave/export', [ReportController::class, 'exportLeaveCsv'])->name('reports.leave.export');
        Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('/reports/payroll/export', [ReportController::class, 'exportPayrollCsv'])->name('reports.payroll.export');

        // Operational Audit Trail (T154)
        Route::get('/audit-logs', [\App\Http\Controllers\HrAdmin\AuditLogViewerController::class, 'index'])->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Employee Self-Service Area
|--------------------------------------------------------------------------
*/
Route::prefix('employee')
    ->name('employee.')
    ->middleware(['auth', 'active', 'role:employee'])
    ->group(function () {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/holidays', [\App\Http\Controllers\Employee\EmployeeHolidayController::class, 'index'])->name('holidays.index');

        // Attendance & Punch (Accessible to all non-admin employee roles: employee, manager, team_lead)
        Route::middleware(['role:employee,manager,team_lead'])->group(function () {
            Route::post('/attendance/punch-in', [PunchAttendanceController::class, 'punchIn'])->name('attendance.punch-in')->middleware('throttle:30,1');
            Route::post('/attendance/punch-out', [PunchAttendanceController::class, 'punchOut'])->name('attendance.punch-out')->middleware('throttle:30,1');
            Route::get('/attendance/history', [PunchAttendanceController::class, 'history'])->name('attendance.history');
        });

        // Leaves & Payslips
        Route::get('/leaves', [LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/apply', [LeaveRequestController::class, 'create'])->name('leaves.create');
        Route::post('/leaves/apply', [LeaveRequestController::class, 'store'])->name('leaves.store');
        Route::post('/leaves/{id}/cancel', [LeaveRequestController::class, 'cancel'])->name('leaves.cancel');
        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslips/{id}/view', [PayslipController::class, 'view'])->name('payslips.view');
        Route::get('/payslips/{id}/download', [PayslipController::class, 'download'])->name('payslips.download');

        // Timesheets (Phase 25: Tasks T236 - T242)
        Route::get('/timesheets', [EmployeeTimesheetController::class, 'index'])->name('timesheets.index');
        Route::get('/timesheets/create', [EmployeeTimesheetController::class, 'create'])->name('timesheets.create');
        Route::post('/timesheets', [EmployeeTimesheetController::class, 'store'])->name('timesheets.store');
        Route::get('/timesheets/{timesheet}', [EmployeeTimesheetController::class, 'show'])->name('timesheets.show');
        Route::post('/timesheets/{timesheet}/entries', [EmployeeTimesheetController::class, 'storeEntry'])->name('timesheets.entries.store');
        Route::delete('/timesheets/{timesheet}/entries/{entry}', [EmployeeTimesheetController::class, 'destroyEntry'])->name('timesheets.entries.destroy');
        Route::post('/timesheets/{timesheet}/submit', [EmployeeTimesheetController::class, 'submit'])->name('timesheets.submit');
        Route::delete('/timesheets/{timesheet}', [EmployeeTimesheetController::class, 'destroy'])->name('timesheets.destroy');

        // Project Knowledge Base & Document Downloads (Phase 27: Tasks T254 - T255)
        Route::get('/knowledge', [KnowledgeSearchController::class, 'index'])->name('knowledge.index');
        Route::get('/projects/{project}/documents/{document}/download/{version?}', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');

        // Employee Task Views (Task Assignment & Recurring Task Visibility)
        Route::get('/tasks', [EmployeeTaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/recurring', [EmployeeTaskController::class, 'recurring'])->name('tasks.recurring');
        Route::get('/tasks/{task}', [EmployeeTaskController::class, 'show'])->name('tasks.show');
        Route::post('/tasks/{task}/status', [EmployeeTaskController::class, 'updateStatus'])->name('tasks.status');
        Route::post('/tasks/{task}/comments', [EmployeeTaskController::class, 'storeComment'])->name('tasks.comments.store');
    });

/*
|--------------------------------------------------------------------------
| Manager Area (Project Module)
|--------------------------------------------------------------------------
*/
Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'active', 'role:super_admin,manager'])
    ->group(function () {
        Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');

        // Client Management (Phase 21: Tasks T207 - T213)
        Route::get('/clients', [ClientManagementController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientManagementController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientManagementController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}', [ClientManagementController::class, 'show'])->name('clients.show');
        Route::get('/clients/{client}/edit', [ClientManagementController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientManagementController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientManagementController::class, 'destroy'])->name('clients.destroy');

        // Contacts (T208)
        Route::post('/clients/{client}/contacts', [ClientManagementController::class, 'storeContact'])->name('clients.contacts.store');
        Route::put('/clients/{client}/contacts/{contact}', [ClientManagementController::class, 'updateContact'])->name('clients.contacts.update');
        Route::delete('/clients/{client}/contacts/{contact}', [ClientManagementController::class, 'destroyContact'])->name('clients.contacts.destroy');
        Route::post('/clients/{client}/contacts/{contact}/primary', [ClientManagementController::class, 'setPrimaryContact'])->name('clients.contacts.primary');

        // Projects Linkage (T209)
        Route::post('/clients/{client}/projects/link', [ClientManagementController::class, 'linkProject'])->name('clients.projects.link');
        Route::delete('/clients/{client}/projects/{project}/unlink', [ClientManagementController::class, 'unlinkProject'])->name('clients.projects.unlink');

        // Documents (T210)
        Route::post('/clients/{client}/documents', [ClientManagementController::class, 'uploadDocument'])->name('clients.documents.store');
        Route::get('/clients/{client}/documents/{document}/download', [ClientManagementController::class, 'downloadDocument'])->name('clients.documents.download');
        Route::post('/clients/{client}/documents/{document}/toggle-share', [ClientManagementController::class, 'toggleDocumentSharing'])->name('clients.documents.toggle-share');
        Route::delete('/clients/{client}/documents/{document}', [ClientManagementController::class, 'destroyDocument'])->name('clients.documents.destroy');

        // Communications (T211)
        Route::post('/clients/{client}/communications', [ClientManagementController::class, 'storeCommunication'])->name('clients.communications.store');
        Route::delete('/clients/{client}/communications/{communication}', [ClientManagementController::class, 'destroyCommunication'])->name('clients.communications.destroy');

        // Portal Users (T212)
        Route::post('/clients/{client}/portal-users', [ClientManagementController::class, 'storePortalUser'])->name('clients.portal-users.store');
        Route::post('/clients/{client}/portal-users/{user}/toggle-status', [ClientManagementController::class, 'togglePortalUserStatus'])->name('clients.portal-users.toggle-status');
        Route::delete('/clients/{client}/portal-users/{user}', [ClientManagementController::class, 'destroyPortalUser'])->name('clients.portal-users.destroy');

        // Team Management (Phase 22: Tasks T214 - T216, T219)
        Route::get('/teams', [TeamManagementController::class, 'index'])->name('teams.index');
        Route::get('/teams/create', [TeamManagementController::class, 'create'])->name('teams.create');
        Route::post('/teams', [TeamManagementController::class, 'store'])->name('teams.store');
        Route::get('/teams/{team}', [TeamManagementController::class, 'show'])->name('teams.show');
        Route::get('/teams/{team}/edit', [TeamManagementController::class, 'edit'])->name('teams.edit');
        Route::put('/teams/{team}', [TeamManagementController::class, 'update'])->name('teams.update');
        Route::delete('/teams/{team}', [TeamManagementController::class, 'destroy'])->name('teams.destroy');

        // Team Membership (T215)
        Route::post('/teams/{team}/members', [TeamManagementController::class, 'addMember'])->name('teams.members.add');
        Route::post('/teams/{team}/members/{member}/primary', [TeamManagementController::class, 'setPrimary'])->name('teams.members.primary');
        Route::delete('/teams/{team}/members/{member}', [TeamManagementController::class, 'removeMember'])->name('teams.members.remove');

        // Project Employee Profiles (Phase 22: Tasks T217 - T218)
        Route::get('/employees/profiles', [ProjectEmployeeProfileController::class, 'index'])->name('employees.profiles.index');
        Route::get('/employees/{employee}/project-profile', [ProjectEmployeeProfileController::class, 'show'])->name('employees.profiles.show');
        Route::get('/employees/{employee}/project-profile/edit', [ProjectEmployeeProfileController::class, 'edit'])->name('employees.profiles.edit');
        Route::put('/employees/{employee}/project-profile', [ProjectEmployeeProfileController::class, 'update'])->name('employees.profiles.update');

        // Project Management (Phase 23: Tasks T220 - T225)
        Route::get('/projects', [ProjectManagementController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [ProjectManagementController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectManagementController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectManagementController::class, 'show'])->name('projects.show');
        Route::get('/projects/{project}/edit', [ProjectManagementController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [ProjectManagementController::class, 'update'])->name('projects.update');
        Route::post('/projects/{project}/status', [ProjectManagementController::class, 'updateStatus'])->name('projects.status');
        Route::delete('/projects/{project}', [ProjectManagementController::class, 'destroy'])->name('projects.destroy');
        // Project Documents (Phase 27: Tasks T249 - T254)
        Route::get('/projects/{project}/documents', [ProjectDocumentController::class, 'index'])->name('projects.documents.index');
        Route::post('/projects/{project}/documents', [ProjectDocumentController::class, 'store'])->name('projects.documents.store');
        Route::get('/projects/{project}/documents/{document}/download/{version?}', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');
        Route::post('/projects/{project}/documents/{document}/toggle-share', [ProjectDocumentController::class, 'toggleShare'])->name('projects.documents.toggle-share');
        Route::delete('/projects/{project}/documents/{document}', [ProjectDocumentController::class, 'destroy'])->name('projects.documents.destroy');

        // Project Knowledge Base Search (Phase 27: Task T255)
        Route::get('/knowledge', [KnowledgeSearchController::class, 'index'])->name('knowledge.index');
        
        // Project Milestones (Phase 23: Task T221)
        Route::post('/projects/{project}/milestones', [ProjectManagementController::class, 'storeMilestone'])->name('projects.milestones.store');
        Route::put('/projects/{project}/milestones/{milestone}', [ProjectManagementController::class, 'updateMilestone'])->name('projects.milestones.update');
        Route::post('/projects/{project}/milestones/{milestone}/toggle', [ProjectManagementController::class, 'toggleMilestoneStatus'])->name('projects.milestones.toggle');
        Route::delete('/projects/{project}/milestones/{milestone}', [ProjectManagementController::class, 'destroyMilestone'])->name('projects.milestones.destroy');

        // Project Members Management
        Route::post('/projects/{project}/members', [ProjectManagementController::class, 'addMember'])->name('projects.members.add');
        Route::delete('/projects/{project}/members/{member}', [ProjectManagementController::class, 'removeMember'])->name('projects.members.remove');

        // Task Management (Phase 24: Tasks T226 - T235)
        Route::get('/tasks', [TaskManagementController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/kanban', [TaskManagementController::class, 'kanban'])->name('tasks.kanban');
        Route::get('/tasks/create', [TaskManagementController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskManagementController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [TaskManagementController::class, 'show'])->name('tasks.show');
        Route::get('/tasks/{task}/edit', [TaskManagementController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskManagementController::class, 'update'])->name('tasks.update');
        Route::post('/tasks/{task}/status', [TaskManagementController::class, 'updateStatus'])->name('tasks.status');
        Route::delete('/tasks/{task}', [TaskManagementController::class, 'destroy'])->name('tasks.destroy');

        // Task Checklists (Task T230)
        Route::post('/tasks/{task}/checklists', [TaskManagementController::class, 'storeChecklist'])->name('tasks.checklists.store');
        Route::post('/tasks/{task}/checklists/{checklist}/toggle', [TaskManagementController::class, 'toggleChecklist'])->name('tasks.checklists.toggle');
        Route::delete('/tasks/{task}/checklists/{checklist}', [TaskManagementController::class, 'destroyChecklist'])->name('tasks.checklists.destroy');

        // Task Dependencies (Task T228)
        Route::post('/tasks/{task}/dependencies', [TaskManagementController::class, 'storeDependency'])->name('tasks.dependencies.store');
        Route::delete('/tasks/{task}/dependencies/{dependency}', [TaskManagementController::class, 'destroyDependency'])->name('tasks.dependencies.destroy');

        // Task Comments (Task T230)
        Route::post('/tasks/{task}/comments', [TaskManagementController::class, 'storeComment'])->name('tasks.comments.store');
        Route::delete('/tasks/{task}/comments/{comment}', [TaskManagementController::class, 'destroyComment'])->name('tasks.comments.destroy');

        // Task Attachments (Task T231)
        Route::post('/tasks/{task}/attachments', [TaskManagementController::class, 'storeAttachment'])->name('tasks.attachments.store');
        Route::get('/tasks/{task}/attachments/{attachment}/download', [TaskManagementController::class, 'downloadAttachment'])->name('tasks.attachments.download');
        Route::delete('/tasks/{task}/attachments/{attachment}', [TaskManagementController::class, 'destroyAttachment'])->name('tasks.attachments.destroy');

        // Timesheets Approvals (Phase 25: Tasks T239, T240)
        Route::get('/timesheets/approvals', [TimesheetApprovalController::class, 'index'])->name('timesheets.index');
        Route::get('/timesheets/approvals/{timesheet}', [TimesheetApprovalController::class, 'show'])->name('timesheets.show');
        Route::post('/timesheets/approvals/{timesheet}/approve', [TimesheetApprovalController::class, 'approve'])->name('timesheets.approve');
        Route::post('/timesheets/approvals/{timesheet}/reject', [TimesheetApprovalController::class, 'reject'])->name('timesheets.reject');
        Route::post('/timesheets/approvals/{timesheet}/return', [TimesheetApprovalController::class, 'returnForRevision'])->name('timesheets.return');

        // Productivity & Reporting (Phase 28: Tasks T256 - T261)
        Route::get('/reports/executive', [ProjectReportController::class, 'executive'])->name('reports.executive');
        Route::get('/reports/productivity', [ProjectReportController::class, 'productivity'])->name('reports.productivity');
        Route::get('/reports/workload', [ProjectReportController::class, 'workload'])->name('reports.workload');
        Route::get('/reports/budget', [ProjectReportController::class, 'budget'])->name('reports.budget');
        Route::get('/reports/export/{type}', [ProjectReportController::class, 'export'])->name('reports.export');
    });

/*
|--------------------------------------------------------------------------
| Team Lead Area (Project Module)
|--------------------------------------------------------------------------
*/
Route::prefix('team-lead')
    ->name('team-lead.')
    ->middleware(['auth', 'active', 'role:super_admin,manager,team_lead'])
    ->group(function () {
        Route::get('/dashboard', [TeamLeadDashboardController::class, 'index'])->name('dashboard');
        Route::get('/team', [TeamLeadTeamController::class, 'index'])->name('team.index');
        Route::get('/team/members/{employee}', [TeamLeadTeamController::class, 'showMember'])->name('team.members.show');
        Route::get('/tasks', [TeamLeadTaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/{task}', [TeamLeadTaskController::class, 'show'])->name('tasks.show');

        // Project Documents & Knowledge (Phase 27)
        Route::get('/projects/{project}/documents', [ProjectDocumentController::class, 'index'])->name('projects.documents.index');
        Route::post('/projects/{project}/documents', [ProjectDocumentController::class, 'store'])->name('projects.documents.store');
        Route::get('/projects/{project}/documents/{document}/download/{version?}', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');
        Route::get('/knowledge', [KnowledgeSearchController::class, 'index'])->name('knowledge.index');

        // Squad Timesheet Approvals (Phase 25: Tasks T239, T240)
        Route::get('/timesheets/approvals', [TeamLeadTimesheetApprovalController::class, 'index'])->name('timesheets.index');
        Route::get('/timesheets/approvals/{timesheet}', [TeamLeadTimesheetApprovalController::class, 'show'])->name('timesheets.show');
        Route::post('/timesheets/approvals/{timesheet}/approve', [TeamLeadTimesheetApprovalController::class, 'approve'])->name('timesheets.approve');
        Route::post('/timesheets/approvals/{timesheet}/return', [TeamLeadTimesheetApprovalController::class, 'returnForRevision'])->name('timesheets.return');

        // Productivity & Reporting (Phase 28: Tasks T257, T258, T260, T261)
        Route::get('/reports/productivity', [ProjectReportController::class, 'productivity'])->name('reports.productivity');
        Route::get('/reports/workload', [ProjectReportController::class, 'workload'])->name('reports.workload');
        Route::get('/reports/export/{type}', [ProjectReportController::class, 'export'])->name('reports.export');
    });

/*
|--------------------------------------------------------------------------
| Client Portal Area (Strictly Read-Only — Phase 26: Tasks T243 - T248)
|--------------------------------------------------------------------------
*/
Route::prefix('client-portal')
    ->name('client-portal.')
    ->middleware(['auth', 'active', 'role:client'])
    ->group(function () {
        Route::get('/dashboard', [ClientPortalDashboardController::class, 'index'])->name('dashboard');
        Route::get('/projects/{project}', [ClientPortalDashboardController::class, 'project'])->name('projects.show');
        Route::get('/documents', [ClientPortalDashboardController::class, 'documents'])->name('documents.index');
        Route::get('/documents/{document}/download', [ClientPortalDashboardController::class, 'downloadDocument'])->name('documents.download');
        
        // Client Project Documents Download & Knowledge Search (Phase 27)
        Route::get('/projects/{project}/documents/{document}/download/{version?}', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');
        Route::get('/knowledge', [KnowledgeSearchController::class, 'index'])->name('knowledge.index');
    });
/*
|--------------------------------------------------------------------------
| OAuth 2.1 / Gemini MCP Discovery Metadata
|--------------------------------------------------------------------------
| Discovery endpoints (/.well-known/oauth-authorization-server and
| /.well-known/oauth-protected-resource) are automatically registered
| by Mcp::oauthRoutes() in AppServiceProvider with the correct
| 'mcp:use' scope required by laravel/mcp v0.9.3.
|
| The legacy /api/mcp/login endpoint has been removed.
| All MCP authentication is now exclusively via OAuth 2.0 PKCE.
*/
