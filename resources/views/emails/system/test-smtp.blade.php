@extends('emails.layouts.base')

@section('content')
<h2 style="color: #1e1b4b; font-size: 18px; margin-top: 0;">SMTP Email Service Test</h2>

<p>Hello <strong>{{ $recipient }}</strong>,</p>

<p>This is a test notification confirming that the outgoing SMTP email service for <strong>{{ config('app.name', 'HRM System') }}</strong> is configured properly and successfully delivering messages.</p>

<div class="info-box">
    <table style="width: 100%; font-size: 13px; color: #334155;">
        <tr>
            <td style="padding: 4px 0; font-weight: 600; width: 140px;">Status:</td>
            <td><span class="badge-success">Operational / Connected</span></td>
        </tr>
        <tr>
            <td style="padding: 4px 0; font-weight: 600;">Mail Driver:</td>
            <td><code>{{ config('mail.default') }}</code></td>
        </tr>
        <tr>
            <td style="padding: 4px 0; font-weight: 600;">SMTP Host:</td>
            <td>{{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 0; font-weight: 600;">From Address:</td>
            <td>{{ config('mail.from.address') }} ({{ config('mail.from.name') }})</td>
        </tr>
        <tr>
            <td style="padding: 4px 0; font-weight: 600;">Timestamp:</td>
            <td>{{ $timestamp }}</td>
        </tr>
    </table>
</div>

<p style="font-size: 13px; color: #64748b;">
    If you received this message, password reset links, leave request notifications, task assignments, and payslip finalization alerts will be delivered smoothly to your users.
</p>
@endsection
