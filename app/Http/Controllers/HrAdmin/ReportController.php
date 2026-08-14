<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function attendance(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');

        $query = AttendanceRecord::with('employee:id,first_name,last_name,employee_code,department')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);

        $records = $query->latest('attendance_date')->paginate(25)->withQueryString();
        
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $departments = Employee::whereNotNull('department')->distinct()->pluck('department');

        return view('hr-admin.reports.attendance', compact('records', 'employees', 'departments', 'startDate', 'endDate', 'employeeId', 'department', 'status'));
    }

    public function exportAttendanceCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');

        $query = AttendanceRecord::with('employee')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);

        $records = $query->orderBy('attendance_date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Status', 'Punch In', 'Punch Out', 'Total Hours', 'IP Address']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->attendance_date,
                    $r->employee?->employee_code,
                    $r->employee?->first_name . ' ' . $r->employee?->last_name,
                    $r->employee?->department,
                    $r->status instanceof \App\Enums\AttendanceStatus ? $r->status->value : (string) $r->status,
                    $r->punch_in_at ?? ($r->punch_in_time ?? '-'),
                    $r->punch_out_at ?? ($r->punch_out_time ?? '-'),
                    $r->total_hours ?? '0.00',
                    $r->ip_address ?? '-',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function leave(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');

        $query = LeaveRequest::with(['employee:id,first_name,last_name,employee_code,department', 'leaveType:id,name'])
            ->whereBetween('start_date', [$startDate, $endDate]);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);

        $records = $query->latest('start_date')->paginate(25)->withQueryString();
        
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $departments = Employee::whereNotNull('department')->distinct()->pluck('department');

        return view('hr-admin.reports.leave', compact('records', 'employees', 'departments', 'startDate', 'endDate', 'employeeId', 'department', 'status'));
    }

    public function exportLeaveCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->whereBetween('start_date', [$startDate, $endDate]);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);

        $records = $query->orderBy('start_date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leave_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Total Days', 'Half Day', 'Status', 'Reason']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->employee?->employee_code,
                    $r->employee?->first_name . ' ' . $r->employee?->last_name,
                    $r->employee?->department,
                    $r->leaveType?->name,
                    $r->start_date,
                    $r->end_date,
                    $r->total_days,
                    $r->is_half_day ? 'Yes (' . $r->half_day_type . ')' : 'No',
                    $r->status instanceof \App\Enums\LeaveStatus ? $r->status->value : (string) $r->status,
                    $r->reason ?? '-',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function payroll(Request $request): View
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');

        $query = Payroll::with('employee:id,first_name,last_name,employee_code,department')
            ->where('payroll_year', $year)
            ->where('payroll_month', $month);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);
        if ($paymentStatus) $query->where('payment_status', $paymentStatus);

        $records = $query->latest('net_salary')->paginate(25)->withQueryString();
        
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $departments = Employee::whereNotNull('department')->distinct()->pluck('department');

        $allFiltered = $query->get();
        $stats = [
            'total_gross' => (float) $allFiltered->sum('monthly_salary'),
            'total_lop' => (float) $allFiltered->sum('lop_deduction_amount'),
            'total_net' => (float) $allFiltered->sum('net_salary'),
        ];

        return view('hr-admin.reports.payroll', compact('records', 'employees', 'departments', 'stats', 'year', 'month', 'employeeId', 'department', 'status', 'paymentStatus'));
    }

    public function exportPayrollCsv(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $employeeId = $request->input('employee_id');
        $department = $request->input('department');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');

        $query = Payroll::with('employee')
            ->where('payroll_year', $year)
            ->where('payroll_month', $month);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($department) $query->whereHas('employee', fn($q) => $q->where('department', $department));
        if ($status) $query->where('status', $status);
        if ($paymentStatus) $query->where('payment_status', $paymentStatus);

        $records = $query->orderBy('id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payroll_report_' . $year . '_' . $month . '_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records, $year, $month) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Period', 'Employee Code', 'Employee Name', 'Department', 'Monthly Gross', 'Daily Rate', 'LOP Days', 'LOP Deduction', 'Net Salary', 'Workflow Status', 'Payment Status']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                    $r->employee?->employee_code,
                    $r->employee?->first_name . ' ' . $r->employee?->last_name,
                    $r->employee?->department,
                    $r->monthly_salary,
                    $r->daily_salary,
                    $r->total_lop_days,
                    $r->lop_deduction_amount,
                    $r->net_salary,
                    $r->status instanceof \App\Enums\PayrollStatus ? $r->status->value : (string) $r->status,
                    $r->payment_status instanceof \App\Enums\PaymentStatus ? $r->payment_status->value : (string) $r->payment_status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}