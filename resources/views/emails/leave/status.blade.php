@extends('emails.layouts.base')

@section('content')
<div style="margin-bottom: 20px;">
    @if($status === 'approved')
        <span class="badge-success">&#10003; Leave Request Approved</span>
    @else
        <span class="badge-danger">&#10007; Leave Request Rejected</span>
    @endif
</div>

<h2 style="color: #1e1b4b; font-size: 18px; margin-top: 0;">
    Leave Application {{ ucfirst($status) }}
</h2>

<p>Hello <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>,</p>

<p>
    Your leave application has been 
    <strong style="color: {{ $status === 'approved' ? '#15803d' : '#b91c1c' }};">{{ $status }}</strong>
    by Human Resources.
</p>

<div class="info-box">
    <table style="font-size: 14px;">
        <tr>
            <td style="padding: 6px 0; color: #64748b; width: 140px;">Leave Type:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">{{ $leave->leaveType?->name ?? 'Leave' }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #64748b;">Duration:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">
                {{ $leave->start_date->format('d M, Y') }} 
                @if($leave->start_date->ne($leave->end_date))
                    to {{ $leave->end_date->format('d M, Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #64748b;">Total Days:</td>
            <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">
                {{ $leave->total_days }} day(s) 
                @if($leave->is_half_day)
                    (Half Day)
                @endif
            </td>
        </tr>
        @if(!empty($reason))
        <tr>
            <td style="padding: 6px 0; color: #64748b; vertical-align: top;">Remarks / Reason:</td>
            <td style="padding: 6px 0; font-weight: 500; color: #b91c1c;">{{ $reason }}</td>
        </tr>
        @endif
    </table>
</div>

<div class="btn-container">
    <a href="{{ $actionUrl ?? url('/employee/leaves') }}" class="btn" target="_blank">View Leave Details</a>
</div>
@endsection
