@extends('emails.layouts.base')

@section('content')
<h2 style="color: #1e1b4b; font-size: 18px; margin-top: 0;">Password Reset Request</h2>

<p>Hello <strong>{{ $user->name ?? $user->username }}</strong>,</p>

<p>We received a request to reset your password for your <strong>{{ config('app.name', 'HRM System') }}</strong> account.</p>

<p>Click the button below to proceed with setting a new password:</p>

<div class="btn-container">
    <a href="{{ $resetUrl }}" class="btn" target="_blank">Reset Password</a>
</div>

<div class="info-box">
    <p style="margin: 0; font-size: 13px; color: #475569;">
        <strong>Security Notice:</strong> This password reset link is valid for <strong>{{ $expireMinutes ?? 60 }} minutes</strong>. If you did not initiate this request, no further action is needed and your account remains secure.
    </p>
</div>

<p style="font-size: 12px; color: #64748b; margin-top: 25px; word-break: break-all;">
    If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
    <a href="{{ $resetUrl }}" style="color: #4f46e5;">{{ $resetUrl }}</a>
</p>
@endsection
