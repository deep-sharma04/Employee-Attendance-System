@extends('layouts.app')

@section('title', 'Company Settings & Business Rules | Super Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">System & Company Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Configure company branding, payslip metadata, attendance grace bounds, and payroll business rules.</p>
        </div>
    </div>

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

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-6 py-2.5 rounded-xl shadow-xs transition-colors">
                Save System Settings & Business Rules
            </button>
        </div>
    </form>
</div>
@endsection
