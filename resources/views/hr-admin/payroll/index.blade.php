@extends('layouts.app')

@section('title', 'Payroll Operations & Approvals')
@section('page-title', 'Monthly Payroll & Salary Disbursements')

@section('content')
<div class="space-y-6">
    <!-- Stat Counter Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Payroll</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">₹{{ number_format($stats['total_gross'] ?? 0, 2) }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ $stats['total_payrolls'] ?? 0 }} Records in cycle</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total LOP Deductions</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-1">₹{{ number_format($stats['total_lop_deductions'] ?? 0, 2) }}</h3>
                <span class="text-[11px] font-medium text-rose-600">Absences & Bridged days</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Salary Payable</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">₹{{ number_format($stats['total_net_pay'] ?? 0, 2) }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Disbursement amount</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approval Breakdown</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-bold text-slate-700">{{ $stats['draft_count'] ?? 0 }} Draft</span> •
                    <span class="text-xs font-bold text-amber-600">{{ $stats['reviewed_count'] ?? 0 }} Review</span> •
                    <span class="text-xs font-bold text-emerald-600">{{ $stats['finalized_count'] ?? 0 }} Final</span>
                </div>
                <span class="text-[11px] font-medium text-indigo-600">{{ $stats['approved_count'] ?? 0 }} Approved by SA</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
        </div>
    </div>

    <!-- Actions & Filter Toolbar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('hr-admin.payroll.index') }}" class="flex-1 grid grid-cols-1 sm:grid-cols-5 gap-3">
                <!-- Year Selector -->
                <select name="year" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                    @endfor
                </select>

                <!-- Month Selector -->
                <select name="month" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>

                <!-- Status Filter -->
                <select name="status" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Workflow Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft Generated</option>
                    <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Under Review</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Super Admin Approved</option>
                    <option value="finalized" {{ request('status') === 'finalized' ? 'selected' : '' }}>Finalized & Locked</option>
                </select>

                <!-- Payment Status Filter -->
                <select name="payment_status" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Payment Statuses</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('payment_status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="cleared" {{ request('payment_status') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                    <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'payment_status']))
                        <a href="{{ route('hr-admin.payroll.index') }}" class="px-2 text-slate-500 hover:text-slate-700 text-xs font-medium">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <!-- Generate Payroll CTA Button -->
            <a href="{{ route('hr-admin.payroll.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 flex-shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Generate Monthly Payroll
            </a>
        </div>
    </div>

    <!-- Payroll Records Listing Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Employee</th>
                        <th class="px-4 py-3.5">Period & Revision</th>
                        <th class="px-4 py-3.5">Monthly Gross</th>
                        <th class="px-4 py-3.5">LOP Days / Deduction</th>
                        <th class="px-4 py-3.5">Net Salary</th>
                        <th class="px-4 py-3.5">Workflow Status</th>
                        <th class="px-4 py-3.5">Payment</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payrolls as $p)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $p->employee?->first_name }} {{ $p->employee?->last_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $p->employee?->employee_code }} • {{ $p->employee?->department }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-semibold text-slate-800">{{ date('F Y', mktime(0, 0, 0, $p->payroll_month, 1, $p->payroll_year)) }}</span>
                                @if($p->revision_number > 1)
                                    <div class="inline-flex items-center gap-1 text-[10px] font-semibold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 mt-0.5">
                                        Rev #{{ $p->revision_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 font-mono font-semibold text-slate-800">
                                ₹{{ number_format($p->monthly_salary, 2) }}
                                <div class="text-[10px] text-slate-400 font-sans">₹{{ number_format($p->daily_salary, 2) }}/day (div 30)</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-rose-600">{{ $p->total_lop_days }} LOP Days</div>
                                <div class="text-[11px] font-mono text-rose-600">-₹{{ number_format($p->lop_deduction_amount, 2) }}</div>
                            </td>
                            <td class="px-4 py-4 font-mono font-bold text-emerald-700 text-sm">
                                ₹{{ number_format($p->net_salary, 2) }}
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $statusValue = $p->status instanceof \App\Enums\PayrollStatus ? $p->status->value : (string) $p->status;
                                @endphp
                                @if($statusValue === 'finalized')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Finalized & Locked
                                    </span>
                                @elseif($statusValue === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 mr-1.5"></span> Approved
                                    </span>
                                @elseif($statusValue === 'reviewed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 mr-1.5"></span> Under Review
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 mr-1.5"></span> Draft Generated
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $payVal = $p->payment_status instanceof \App\Enums\PaymentStatus ? $p->payment_status->value : (string) $p->payment_status;
                                @endphp
                                <form method="POST" action="{{ route('hr-admin.payroll.payment-status', $p->id) }}" class="inline">
                                    @csrf
                                    <select name="payment_status" onchange="this.form.submit()"
                                        class="rounded-lg border border-slate-300 py-1 px-2 text-[11px] font-semibold
                                        {{ $payVal === 'cleared' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ($payVal === 'failed' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-amber-50 text-amber-700 border-amber-300') }}">
                                        <option value="pending" {{ $payVal === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="processing" {{ $payVal === 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="cleared" {{ $payVal === 'cleared' ? 'selected' : '' }}>Cleared</option>
                                        <option value="failed" {{ $payVal === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('hr-admin.payroll.show', $p->id) }}"
                                        class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition-colors">
                                        Review
                                    </a>

                                    @if($statusValue === 'draft')
                                        <form method="POST" action="{{ route('hr-admin.payroll.review', $p->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors" title="Mark as Reviewed">
                                                Mark Reviewed
                                            </button>
                                        </form>
                                    @endif

                                    @php
                                        $userRole = Auth::user()?->role instanceof \App\Enums\UserRole ? Auth::user()->role->value : (string) Auth::user()?->role;
                                    @endphp
                                    @if($userRole === 'super_admin')
                                        @if($statusValue === 'reviewed' || $statusValue === 'draft')
                                            <form method="POST" action="{{ route('hr-admin.payroll.approve', $p->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition-colors" onclick="return confirm('Approve this payroll batch?')">
                                                    Approve
                                                </button>
                                            </form>
                                        @elseif($statusValue === 'approved')
                                            <form method="POST" action="{{ route('hr-admin.payroll.finalize', $p->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-colors" onclick="return confirm('Finalize and lock this payroll? This locks ordinary edits and issues official payslips.')">
                                                    Finalize & Lock
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    @if($statusValue !== 'finalized')
                                        <form method="POST" action="{{ route('hr-admin.payroll.destroy', $p->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete draft" onclick="return confirm('Delete this unfinalized payroll draft?')">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400 text-xs">
                                No payroll records found for the selected period. Click "Generate Monthly Payroll" to start.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payrolls->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $payrolls->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
