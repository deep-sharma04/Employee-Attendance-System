@extends('layouts.app')

@section('title', 'Company Settings & Business Rules | Super Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">System & Company Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Configure company branding, payslip metadata, attendance grace bounds, and outgoing SMTP email services.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-50 p-4 text-xs text-emerald-800 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->has('test_email'))
        <div class="rounded-xl border border-rose-500/30 bg-rose-50 p-4 text-xs text-rose-800 flex items-start gap-2">
            <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $errors->first('test_email') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.settings.update') }}" class="space-y-6">
        @csrf

        <!-- Section 1: Company Profile (Payslip Header) -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Company Branding & Payslip Profile
                </h3>
                <p class="text-xs text-slate-500 mt-1">These details appear on official payslip PDF exports and system headers.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Company Legal Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'HRM Enterprise Inc.') }}" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden font-medium">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Registered Office Address <span class="text-rose-500">*</span></label>
                    <textarea name="company_address" rows="2" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden font-medium">{{ old('company_address', $settings['company_address'] ?? '100 Business Tech Park, Silicon Corridor') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Official HR Contact Email</label>
                    <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? 'hr@hrm.local') }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Official Phone Number</label>
                    <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '+1 (555) 019-2834') }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>
            </div>
        </div>

        <!-- Section 2: Core Business Rules -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                    Attendance & Payroll Business Rules
                </h3>
                <p class="text-xs text-slate-500 mt-1">Configurable calculation parameters governing daily salary, late grace limits, and absence conversions.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Salary Divisor -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Salary Monthly Divisor (Days) <span class="text-rose-500">*</span></label>
                    <input type="number" name="salary_divisor" value="{{ old('salary_divisor', $settings['salary_divisor'] ?? 30) }}" min="20" max="31" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Standard divisor for computing daily salary (<code class="bg-slate-100 px-1 py-0.5 rounded">Monthly Salary / Divisor</code>).</p>
                </div>

                <!-- Late Grace Period -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Late Grace Period (Minutes) <span class="text-rose-500">*</span></label>
                    <input type="number" name="late_grace_period_minutes" value="{{ old('late_grace_period_minutes', $settings['late_grace_period_minutes'] ?? 15) }}" min="0" max="60" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Punches within this threshold past shift start are classified as Present.</p>
                </div>

                <!-- Half Day Threshold -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Half-Day Threshold (Minutes) <span class="text-rose-500">*</span></label>
                    <input type="number" name="half_day_threshold_minutes" value="{{ old('half_day_threshold_minutes', $settings['half_day_threshold_minutes'] ?? 60) }}" min="15" max="180" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Late punches exceeding this duration are classified as Half-Day.</p>
                </div>

                <!-- Late to Absent Ratio -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Late-to-Absent Conversion Ratio <span class="text-rose-500">*</span></label>
                    <input type="number" name="late_to_absent_ratio" value="{{ old('late_to_absent_ratio', $settings['late_to_absent_ratio'] ?? 3) }}" min="1" max="10" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Number of late marks converted to 1 full unpaid absence day.</p>
                </div>

                <!-- Half-Day to Absent Ratio -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Half-Day to Absent Conversion Ratio <span class="text-rose-500">*</span></label>
                    <input type="number" name="half_day_to_absent_ratio" value="{{ old('half_day_to_absent_ratio', $settings['half_day_to_absent_ratio'] ?? 2) }}" min="1" max="10" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Number of half-days converted to 1 full unpaid absence day.</p>
                </div>

                <!-- Sandwich Rule Toggle -->
                <div class="sm:col-span-2 flex items-center gap-2 pt-2">
                    <input type="checkbox" name="enable_sandwich_rule" value="1" id="enableSandwich" @checked(old('enable_sandwich_rule', $settings['enable_sandwich_rule'] ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="enableSandwich" class="text-xs text-slate-700 font-medium">Enable Holiday Sandwich Rule (unapproved absences bridging weekends/holidays are deducted as LOP)</label>
                </div>
            </div>
        </div>

        <!-- Section 3: SMTP & Outgoing Email Service -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    SMTP & Outgoing Email Service
                </h3>
                <p class="text-xs text-slate-500 mt-1">Configure SMTP relay server credentials for password recovery, attendance notifications, and payslips.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Mail Driver / Transport</label>
                    <select name="mail_mailer" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                        <option value="smtp" @selected(old('mail_mailer', $settings['mail_mailer'] ?? 'smtp') === 'smtp')>SMTP (Recommended)</option>
                        <option value="sendmail" @selected(old('mail_mailer', $settings['mail_mailer'] ?? '') === 'sendmail')>Sendmail</option>
                        <option value="log" @selected(old('mail_mailer', $settings['mail_mailer'] ?? '') === 'log')>Log (Testing / Local Only)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">SMTP Host</label>
                    <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? 'smtp.gmail.com') }}" placeholder="smtp.gmail.com" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">SMTP Port</label>
                    <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? 465) }}" placeholder="465" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Encryption</label>
                    <select name="mail_encryption" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                        <option value="ssl" @selected(old('mail_encryption', $settings['mail_encryption'] ?? 'ssl') === 'ssl')>SSL (Port 465)</option>
                        <option value="tls" @selected(old('mail_encryption', $settings['mail_encryption'] ?? '') === 'tls')>TLS (Port 587)</option>
                        <option value="null" @selected(old('mail_encryption', $settings['mail_encryption'] ?? '') === 'null' || old('mail_encryption', $settings['mail_encryption'] ?? '') === '')>None (Port 25/2525)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">SMTP Username / Email</label>
                    <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}" placeholder="your-email@gmail.com" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">SMTP Password / App Password</label>
                    <input type="password" name="mail_password" placeholder="{{ !empty($settings['mail_password']) ? '•••••••••••••••• (Leave blank to keep)' : '16-digit Google App Password or secret' }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">From Sender Address</label>
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? 'noreply@hrm.local') }}" placeholder="noreply@company.com" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">From Sender Name</label>
                    <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'HRM System') }}" placeholder="HRM System" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-6 py-2.5 rounded-xl shadow-xs transition-colors cursor-pointer">
                Save System Settings & Email Config
            </button>
        </div>
    </form>

    <!-- Test SMTP Email Tool Card -->
    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Test SMTP Connection & Dispatch
                </h4>
                <p class="text-xs text-slate-500 mt-0.5">Send a test email to verify your SMTP host connectivity and authentication credentials in real time.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.settings.mail-test') }}" class="flex flex-col sm:flex-row items-center gap-3">
            @csrf
            <div class="w-full sm:flex-1">
                <input type="email" name="test_email" value="{{ old('test_email', auth()->user()->email ?? $settings['company_email'] ?? '') }}" required placeholder="recipient@example.com" class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
            </div>
            <button type="submit" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-5 py-2.5 rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <span>Send SMTP Test Email</span>
            </button>
        </form>
    </div>
</div>
@endsection
