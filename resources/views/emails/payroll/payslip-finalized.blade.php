@extends('emails.layouts.base')

@section('content')
<div style="margin-bottom: 20px;">
    <span class="badge-success">&#10003; Payslip Finalized & Released</span>
</div>

<h2 style="color: #1e1b4b; font-size: 18px; margin-top: 0;">
    Your Payslip for {{ $monthName }} is Ready
</h2>

<p>Hello <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>,</p>

<p>
    Your monthly salary slip for <strong>{{ $monthName }}</strong> has been officially generated and finalized. 
    @if($hasAttachment)
        A PDF copy of your payslip is attached to this email.
    @endif
</p>

<div class="info-box">
    <table style="font-size: 14px;">
        <tr>
            <td style="padding: 6px 0; color: #64748b; width: 150px;">Employee Code:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">{{ $employee->employee_code }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #64748b;">Pay Period:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">{{ $monthName }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #64748b;">Monthly Base Salary:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">{{ number_format($payroll->monthly_salary, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #64748b;">LOP Days / Deduction:</td>
            <td style="padding: 6px 0; font-weight: 600; color: {{ ($payroll->total_lop_days ?? 0) > 0 ? '#b91c1c' : '#0f172a' }};">
                {{ $payroll->total_lop_days ?? 0 }} day(s) (-{{ number_format($payroll->lop_deduction_amount ?? 0, 2) }})
            </td>
        </tr>
        <tr style="border-top: 1px solid #cbd5e1;">
            <td style="padding: 10px 0 6px; font-weight: 700; color: #1e1b4b; font-size: 15px;">Net Disbursed Pay:</td>
            <td style="padding: 10px 0 6px; font-weight: 800; color: #4338ca; font-size: 16px;">{{ number_format($payroll->net_salary, 2) }}</td>
        </tr>
    </table>
</div>

<div class="btn-container">
    <a href="{{ $actionUrl ?? url('/employee/payslips') }}" class="btn" target="_blank">Access Employee Portal</a>
</div>

<p style="font-size: 13px; color: #64748b;">
    You can also view, verify, and download all your historical payslips anytime through the Employee Self-Service portal.
</p>
@endsection
