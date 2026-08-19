@extends('emails.layouts.base')

@section('content')
<h2 style="color: #1e1b4b; font-size: 18px; margin-top: 0;">Password Reset Request</h2>

<p>Hello <strong>{{ $user->name ?? $user->username }}</strong>,</p>

<p>We received a password reset request for your account (<strong>{{ $user->username }}</strong>) on <strong>{{ config('app.name', 'HRM System') }}</strong>.</p>

<p>Click the button below to choose a new, secure password:</p>

<div class="btn-container">
    <a href="{{ $resetUrl }}" class="btn" target="_blank" style="color: #ffffff !important;">Reset Password</a>
</div>

<div class="info-box">
    <p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5;">
        <strong>Security Notice:</strong> This password reset link will expire in <strong>{{ $expireMinutes ?? 60 }} minutes</strong>. If you did not make this request, you can safely disregard this email; your current password remains unchanged.
    </p>
</div>

<p style="font-size: 12px; color: #64748b; margin-top: 25px; word-break: break-all; line-height: 1.5;">
    If you're having trouble with the button above, copy and paste this link into your web browser:<br>
    <a href="{{ $resetUrl }}" style="color: #4f46e5; text-decoration: underline;">{{ $resetUrl }}</a>
</p>
@endsection
