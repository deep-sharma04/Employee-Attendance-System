@extends('layouts.app')

@section('title', 'Edit Employee: ' . $employee->full_name)
@section('page-title', 'Edit Employee Profile')

@section('header-actions')
    <a href="{{ route('hr-admin.employees.show', $employee->id) }}"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs transition-all">
        &larr; View Profile
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <form method="POST" action="{{ route('hr-admin.employees.update', $employee->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Section 1: Personal Information -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">1. Personal Information</h3>
                <p class="text-xs text-slate-400">Basic contact and identity profile.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        First Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="first_name" name="first_name" required value="{{ old('first_name', $employee->first_name) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('first_name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Last Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="last_name" name="last_name" required value="{{ old('last_name', $employee->last_name) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('last_name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required value="{{ old('email', $employee->email) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Phone Number <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="phone" name="phone" required value="{{ old('phone', $employee->phone) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="gender" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Gender <span class="text-rose-500">*</span>
                    </label>
                    <select id="gender" name="gender" required
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $employee->gender) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="date_of_birth" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Date of Birth <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required value="{{ old('date_of_birth', optional($employee->date_of_birth)->format('Y-m-d')) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('date_of_birth') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: Employment & Shift Assignment -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">2. Employment & Shift Assignment</h3>
                <p class="text-xs text-slate-400">Department, designation, shift schedule, and organizational status.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="employee_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Employee Code <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="employee_code" name="employee_code" required value="{{ old('employee_code', $employee->employee_code) }}"
                        class="mt-1.5 block w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('employee_code') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="shift_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Assigned Shift <span class="text-rose-500">*</span>
                    </label>
                    <select id="shift_id" name="shift_id" required
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('shift_id', $employee->shift_id) == $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="department" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Department <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="department" name="department" required value="{{ old('department', $employee->department) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('department') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="designation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Designation <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="designation" name="designation" required value="{{ old('designation', $employee->designation) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('designation') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="joining_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Joining Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="joining_date" name="joining_date" required value="{{ old('joining_date', optional($employee->joining_date)->format('Y-m-d')) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('joining_date') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Status <span class="text-rose-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ old('status', $employee->status?->value ?? (string)$employee->status) === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Compensation & Salary -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">3. Compensation & Salary Structure</h3>
                <p class="text-xs text-slate-400">Monthly gross salary used for payroll and loss of pay computations.</p>
            </div>

            <div>
                <label for="monthly_salary" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Monthly Gross Salary (INR ₹) <span class="text-rose-500">*</span>
                </label>
                <div class="mt-1.5 relative rounded-xl shadow-xs">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-xs font-bold">
                        ₹
                    </div>
                    <input type="number" step="0.01" min="0" id="monthly_salary" name="monthly_salary" required value="{{ old('monthly_salary', $employee->monthly_salary) }}"
                        class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 pl-8 pr-4 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono">
                </div>
                @error('monthly_salary') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Section 4: Bank & Statutory Details -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">4. Bank & Statutory Details</h3>
                <p class="text-xs text-slate-400">Bank account and tax identifier details for salary credit.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Bank Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="bank_name" name="bank_name" required value="{{ old('bank_name', $employee->bank_name) }}"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="account_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Account Number <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="account_number" name="account_number" required value="{{ old('account_number', $employee->account_number) }}"
                        class="mt-1.5 block w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="ifsc_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        IFSC Code <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="ifsc_code" name="ifsc_code" required value="{{ old('ifsc_code', $employee->ifsc_code) }}"
                        class="mt-1.5 block w-full uppercase font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="pan_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        PAN Number <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="pan_number" name="pan_number" required value="{{ old('pan_number', $employee->pan_number) }}"
                        class="mt-1.5 block w-full uppercase font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('hr-admin.employees.show', $employee->id) }}"
                class="px-5 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/30 transition-all">
                Save & Update Profile
            </button>
        </div>
    </form>
</div>
@endsection
