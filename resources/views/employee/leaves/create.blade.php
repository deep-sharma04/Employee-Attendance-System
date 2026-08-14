@extends('layouts.app')

@section('title', 'Apply for Leave')
@section('page-title', 'Submit Leave Request')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('employee.leaves.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Back to Leave History & Balances
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-6">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Leave Application Form</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Select your leave type, dates, and reason. Non-working Sundays and declared company holidays are automatically excluded from your leave balance deduction.
            </p>
        </div>

        <!-- Available Balance Pills -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30">
            @foreach($leaveTypes as $type)
                @php
                    $bal = $balances->get($type->id);
                    $rem = $bal ? $bal->remaining_days : 0.0;
                @endphp
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-100 dark:border-slate-700">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $type->name }}</span>
                    <span class="text-xs font-bold {{ $rem > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-500' }}">
                        {{ $rem }}d left
                    </span>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('employee.leaves.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Leave Type -->
                <div>
                    <label for="leave_type_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Leave Type <span class="text-rose-500">*</span>
                    </label>
                    <select id="leave_type_id" name="leave_type_id" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                        <option value="">-- Choose Leave Category --</option>
                        @foreach($leaveTypes as $type)
                            @php
                                $bal = $balances->get($type->id);
                                $rem = $bal ? $bal->remaining_days : 0.0;
                            @endphp
                            <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} ({{ $rem }} days remaining)
                            </option>
                        @endforeach
                    </select>
                    @error('leave_type_id')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Half-Day Option -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Day Duration
                    </label>
                    <div class="flex items-center gap-4 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="is_half_day" value="1" id="is_half_day_toggle" {{ old('is_half_day') ? 'checked' : '' }}
                                class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Half Day Application (0.5 Day)
                        </label>
                    </div>

                    <div id="half_day_options" class="mt-3 flex items-center gap-4 {{ old('is_half_day') ? '' : 'hidden' }}">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                            <input type="radio" name="half_day_type" value="first_half" {{ old('half_day_type', 'first_half') === 'first_half' ? 'checked' : '' }}
                                class="text-indigo-600 focus:ring-indigo-500">
                            First Half (Morning)
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                            <input type="radio" name="half_day_type" value="second_half" {{ old('half_day_type') === 'second_half' ? 'checked' : '' }}
                                class="text-indigo-600 focus:ring-indigo-500">
                            Second Half (Afternoon)
                        </label>
                    </div>
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Start Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                    @error('start_date')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        End Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                    @error('end_date')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Reason Textarea -->
            <div>
                <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    Reason for Leave <span class="text-rose-500">*</span>
                </label>
                <textarea id="reason" name="reason" rows="4" required placeholder="Please describe the purpose of your leave request (e.g. personal family event, medical consultation)..."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notice Callout -->
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 text-xs text-amber-800 dark:text-amber-300">
                <p class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Important Policy Note:
                </p>
                <p class="mt-1">
                    Approved leave automatically syncs to your attendance calendar as "Leave" (not Absent), protecting you from LOP deductions. Once approved, leave requests cannot be self-cancelled.
                </p>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('employee.leaves.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all">
                    Submit Leave Application
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const halfDayToggle = document.getElementById('is_half_day_toggle');
        const halfDayOptions = document.getElementById('half_day_options');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        if (halfDayToggle && halfDayOptions) {
            halfDayToggle.addEventListener('change', function() {
                if (this.checked) {
                    halfDayOptions.classList.remove('hidden');
                    endDateInput.value = startDateInput.value;
                } else {
                    halfDayOptions.classList.add('hidden');
                }
            });
        }

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (halfDayToggle && halfDayToggle.checked) {
                    endDateInput.value = this.value;
                }
            });
        }
    });
</script>
@endsection
