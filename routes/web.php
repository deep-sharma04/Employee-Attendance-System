<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
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
use App\Http\Controllers\SuperAdmin\AuditLogViewerController;
use App\Http\Controllers\SuperAdmin\CompanySettingsController;
use App\Http\Controllers\SuperAdmin\HrAdminManagementController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
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
});

// Authenticated Global Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'updatePassword'])->name('password.change.post');
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

        // HR Admin Management
        Route::get('/hr-admins', [HrAdminManagementController::class, 'index'])->name('hr-admins.index');
        Route::get('/hr-admins/create', [HrAdminManagementController::class, 'create'])->name('hr-admins.create');

        // Company & Payslip Settings
        Route::get('/settings', [CompanySettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [CompanySettingsController::class, 'update'])->name('settings.update');

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
        Route::get('/employees/{id}', [EmployeeManagementController::class, 'show'])->name('employees.show');
        Route::get('/employees/{id}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');

        // Shifts & IP Allowlist & Holidays
        Route::get('/shifts', [ShiftManagementController::class, 'index'])->name('shifts.index');
        Route::get('/ip-allowlists', [IpAllowlistController::class, 'index'])->name('ip-allowlists.index');
        Route::get('/holidays', [HolidayCalendarController::class, 'index'])->name('holidays.index');

        // Attendance & Correction
        Route::get('/attendance', [AttendanceManagementController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/correct', [AttendanceManagementController::class, 'createCorrection'])->name('attendance.correct');

        // Leaves & Documents & Payroll
        Route::get('/leaves', [LeaveManagementController::class, 'index'])->name('leaves.index');
        Route::get('/documents', [DocumentManagementController::class, 'index'])->name('documents.index');
        Route::get('/payroll', [PayrollManagementController::class, 'index'])->name('payroll.index');

        // Reports
        Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/leave', [ReportController::class, 'leave'])->name('reports.leave');
        Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('reports.payroll');
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

        // Attendance & Punch
        Route::post('/attendance/punch-in', [PunchAttendanceController::class, 'history'])->name('attendance.punch-in')->middleware('office.ip');
        Route::post('/attendance/punch-out', [PunchAttendanceController::class, 'history'])->name('attendance.punch-out')->middleware('office.ip');
        Route::get('/attendance/history', [PunchAttendanceController::class, 'history'])->name('attendance.history');

        // Leaves & Payslips
        Route::get('/leaves', [LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/apply', [LeaveRequestController::class, 'create'])->name('leaves.create');
        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    });
