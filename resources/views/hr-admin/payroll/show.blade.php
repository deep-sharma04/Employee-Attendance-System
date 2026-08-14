@extends('layouts.app')

@section('title', 'Review Employee Payroll')
@section('page-title', 'Itemized Payroll Review & Statutory Breakdown')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-xl font-bold text-slate-900">
                    {{ $payroll->employee?->first_name }} {{ $payroll->employee?->last_name }}
                </h2>
                <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $payroll->employee?->employee_code }}
                </span>
                @if($payroll->revision_number > 1)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">
                        Revision #{{ $payroll->revision_number }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Period: <span class="font-bold text-slate-800">{{ date('F Y', mktime(0, 0, 0, $payroll->payroll_month, 1, $payroll->payroll_year)) }}</span> • {{ $payroll->employee?->designation }} ({{ $payroll->employee?->department }})
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('hr-admin.payroll.index', ['year' => $payroll->payroll_year, 'month' => $payroll->payroll_month]) }}"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                &larr; Back to Payroll List
            </a>

            @php
                $statusVal = $payroll->status instanceof \App\Enums\PayrollStatus ? $payroll->status->value : (string) $payroll->status;
                $userRole = Auth::user()?->role instanceof \App\Enums\UserRole ? Auth::user()->role->value : (string) Auth::user()?->role;
            @endphp

            @if($statusVal === 'draft')
                <form method="POST" action="{{ route('hr-admin.payroll.review', $payroll->id) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                        Mark as Reviewed
                    </button>
                </form>
            @endif

            @if($userRole === 'super_admin')
                @if($statusVal === 'reviewed' || $statusVal === 'draft')
                    <form method="POST" action="{{ route('hr-admin.payroll.approve', $payroll->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors" onclick="return confirm('Approve this payroll calculation?')">
                            Super Admin Approve
                        </button>
                    </form>
                @elseif($statusVal === 'approved')
                    <form method="POST" action="{{ route('hr-admin.payroll.finalize', $payroll->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors" onclick="return confirm('Finalize and lock this payroll? This officially locks the payroll from regular edits.')">
                            Finalize & Lock Payroll
                        </button>
                    </form>
                @endif
            @endif

            @if($statusVal === 'finalized')
                <button type="button" onclick="openRevisionModal()"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    + Controlled Revision
                </button>
            @endif
        </div>
    </div>

    <!-- Governance Status Banner -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div>
                <span class="text-[11px] text-slate-400 font-medium block">Approval State</span>
                @if($statusVal === 'finalized')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Finalized & Locked
                    </span>
                @elseif($statusVal === 'approved')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 mr-1.5"></span> Super Admin Approved
                    </span>
                @elseif($statusVal === 'reviewed')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 mr-1.5"></span> Under Review (HR Admin)
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 mr-1.5"></span> Draft Generated
                    </span>
                @endif
            </div>

            <div class="border-l border-slate-200 pl-3">
                <span class="text-[11px] text-slate-400 font-medium block">Payment Status</span>
                @php
                    $payVal = $payroll->payment_status instanceof \App\Enums\PaymentStatus ? $payroll->payment_status->value : (string) $payroll->payment_status;
                @endphp
                <form method="POST" action="{{ route('hr-admin.payroll.payment-status', $payroll->id) }}" class="inline">
                    @csrf
                    <select name="payment_status" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300 py-1 px-2.5 text-xs font-semibold mt-0.5
                        {{ $payVal === 'cleared' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ($payVal === 'failed' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-amber-50 text-amber-700 border-amber-300') }}">
                        <option value="pending" {{ $payVal === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $payVal === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="cleared" {{ $payVal === 'cleared' ? 'selected' : '' }}>Cleared</option>
                        <option value="failed" {{ $payVal === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="text-xs text-slate-500 space-y-0.5 font-mono text-right">
            <div>Generated by: <span class="text-slate-800 font-semibold font-sans">{{ $payroll->generator?->name ?? 'System' }}</span> ({{ $payroll->created_at->format('d M Y, H:i') }})</div>
            @if($payroll->approved_by)
                <div>Approved by: <span class="text-blue-700 font-semibold font-sans">{{ $payroll->approver?->name }}</span> ({{ $payroll->approved_at?->format('d M Y, H:i') }})</div>
            @endif
            @if($payroll->finalized_by)
                <div>Finalized by: <span class="text-emerald-700 font-semibold font-sans">{{ $payroll->finalizer?->name }}</span> ({{ $payroll->finalized_at?->format('d M Y, H:i') }})</div>
            @endif
        </div>
    </div>

    <!-- Main Grid: Attendance / LOP Breakdown & Financial Net Pay -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Attendance & LOP Deductible Days Breakdown (T115) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Attendance & LOP Day Aggregation</h3>
                <span class="text-xs font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                    Total LOP: {{ $payroll->total_lop_days }} Days
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <dt class="text-slate-400">Total Calendar Days</dt>
                    <dd class="text-sm font-bold text-slate-800 mt-0.5">{{ $payroll->total_days_in_month }} Days</dd>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                    <dt class="text-emerald-700">Present Office Days</dt>
                    <dd class="text-sm font-bold text-emerald-800 mt-0.5">{{ $payroll->present_days }} Days</dd>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <dt class="text-slate-500">Late Punches (3 Late = 1 LOP)</dt>
                    <dd class="text-sm font-bold text-slate-800 mt-0.5">
                        {{ $payroll->late_days }} Late &rarr; <span class="text-rose-600">+{{ $payroll->converted_late_absent_days }} LOP</span>
                    </dd>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <dt class="text-slate-500">Half-Days (2 Half = 1 LOP)</dt>
                    <dd class="text-sm font-bold text-slate-800 mt-0.5">
                        {{ $payroll->half_days }} Half &rarr; <span class="text-rose-600">+{{ $payroll->converted_half_day_absent_days }} LOP</span>
                    </dd>
                </div>
                <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-100">
                    <dt class="text-rose-700 font-semibold">Direct Absences</dt>
                    <dd class="text-sm font-bold text-rose-800 mt-0.5">+{{ $payroll->absent_days }} Days</dd>
                </div>
                <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                    <dt class="text-amber-700 font-semibold">Bridged Holidays (Sandwich)</dt>
                    <dd class="text-sm font-bold text-amber-800 mt-0.5">+{{ $payroll->bridged_holiday_days }} Days</dd>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100">
                    <dt class="text-blue-700">Approved Paid Leaves</dt>
                    <dd class="text-sm font-bold text-blue-800 mt-0.5">{{ $payroll->leave_days }} Days (No LOP)</dd>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <dt class="text-slate-400">Week-off / Sundays</dt>
                    <dd class="text-sm font-bold text-slate-800 mt-0.5">{{ $payroll->weekend_days }} Days</dd>
                </div>
            </dl>
        </div>

        <!-- 2. Financial Calculation Engine (T114, T116, T117) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                    <h3 class="text-sm font-bold text-slate-900">Salary Calculation Breakdown</h3>
                    <span class="text-xs text-slate-500 font-mono">Divisor: {{ $payroll->salary_divisor }} Days</span>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Monthly Gross Salary:</span>
                        <span class="font-mono font-bold text-slate-900">₹{{ number_format($payroll->monthly_salary, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Daily Salary Rate (Salary / 30):</span>
                        <span class="font-mono font-semibold text-slate-700">₹{{ number_format($payroll->daily_salary, 2) }} / day</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-100 text-rose-600">
                        <span>LOP Deduction ({{ $payroll->total_lop_days }} days × ₹{{ number_format($payroll->daily_salary, 2) }}):</span>
                        <span class="font-mono font-bold">-₹{{ number_format($payroll->lop_deduction_amount, 2) }}</span>
                    </div>

                    @foreach($payroll->items->where('type', 'deduction')->where('category', '!=', 'lop_deduction') as $dedItem)
                        <div class="flex items-center justify-between py-1 border-b border-slate-100 text-slate-600">
                            <span>{{ $dedItem->label }}:</span>
                            <span class="font-mono font-semibold text-rose-600">-₹{{ number_format($dedItem->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Net Salary Highlight -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 mt-4 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Final Net Salary Payable</span>
                    <p class="text-[11px] text-emerald-700">Monthly Gross − LOP Deductions − Other Deductions</p>
                </div>
                <div class="text-2xl font-black font-mono text-emerald-800">
                    ₹{{ number_format($payroll->net_salary, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Breakdown & Revision Trail -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Itemized Line Items Table -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs lg:col-span-2">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Itemized Payslip Line Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[11px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Category & Label</th>
                            <th class="px-4 py-2.5">Type</th>
                            <th class="px-4 py-2.5 text-right">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payroll->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $item->label }}</td>
                                <td class="px-4 py-3 capitalize">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $item->type === 'earning' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $item->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold {{ $item->type === 'earning' ? 'text-slate-900' : 'text-rose-600' }}">
                                    {{ $item->type === 'earning' ? '+' : '-' }}₹{{ number_format($item->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bank Details & Revision Trail -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Disbursement Bank Profile</h3>
            <dl class="space-y-2 text-xs">
                <div>
                    <dt class="text-slate-400 font-medium">Bank Name</dt>
                    <dd class="font-bold text-slate-800">{{ $payroll->employee?->bank_name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 font-medium">Account Number</dt>
                    <dd class="font-mono font-bold text-slate-800">{{ $payroll->employee?->account_number ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 font-medium">IFSC Code</dt>
                    <dd class="font-mono text-slate-800">{{ $payroll->employee?->ifsc_code ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 font-medium">PAN Number</dt>
                    <dd class="font-mono text-slate-800">{{ $payroll->employee?->pan_number ?? 'N/A' }}</dd>
                </div>
            </dl>

            @if($revisions->count() > 0)
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 mb-2">Previous Revisions</h4>
                    <div class="space-y-2 text-xs">
                        @foreach($revisions as $rev)
                            <a href="{{ route('hr-admin.payroll.show', $rev->id) }}" class="block p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200">
                                <div class="flex items-center justify-between font-bold text-slate-800">
                                    <span>Revision #{{ $rev->revision_number }}</span>
                                    <span>₹{{ number_format($rev->net_salary, 2) }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $rev->revision_reason ?? 'Adjusted record' }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Revision Modal (T123) -->
<div id="revision-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-1">Create Controlled Payroll Revision</h3>
        <p class="text-xs text-slate-500 mb-4">Initialize Revision #{{ $payroll->revision_number + 1 }} to correct calculations on finalized payroll.</p>

        <form method="POST" action="{{ route('hr-admin.payroll.revision', $payroll->id) }}">
            @csrf
            <div class="mb-4">
                <label for="revision_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Authorized Revision Justification <span class="text-rose-500">*</span>
                </label>
                <textarea name="revision_reason" id="revision_reason" rows="3" required placeholder="State exact rationale (e.g. Approved retrospective attendance correction on 14 Aug, adjusted LOP)..."
                    class="w-full rounded-xl border border-slate-300 p-3 text-xs focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRevisionModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    Confirm & Start Revision #{{ $payroll->revision_number + 1 }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRevisionModal() {
        document.getElementById('revision-modal').classList.remove('hidden');
    }
    function closeRevisionModal() {
        document.getElementById('revision-modal').classList.add('hidden');
    }
</script>
@endsection
