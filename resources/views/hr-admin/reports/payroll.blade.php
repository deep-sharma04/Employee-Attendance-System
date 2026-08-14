@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Payroll Reports</h1>

    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <form method="GET" action="{{ route('hr-admin.reports.payroll') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500">Year</label>
                <input type="number" name="year" value="{{ $year }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Month</label>
                <select name="month" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" @selected($month == $i)>{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Employee</label>
                <select name="employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>{{ $emp->first_name }} {{ $emp->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Department</label>
                <select name="department" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected($department == $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Payroll Status</label>
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All</option>
                    <option value="draft" @selected($status == 'draft')>Draft</option>
                    <option value="approved" @selected($status == 'approved')>Approved</option>
                    <option value="finalized" @selected($status == 'finalized')>Finalized</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Payment Status</label>
                <select name="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All</option>
                    <option value="pending" @selected($paymentStatus == 'pending')>Pending</option>
                    <option value="processing" @selected($paymentStatus == 'processing')>Processing</option>
                    <option value="cleared" @selected($paymentStatus == 'cleared')>Cleared</option>
                    <option value="failed" @selected($paymentStatus == 'failed')>Failed</option>
                </select>
            </div>
            <div class="md:col-span-6 flex justify-end gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-xs transition-colors">Apply Filters</button>
                <a href="{{ route('hr-admin.reports.payroll.export', request()->all()) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Export CSV
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
            <p class="text-sm text-gray-500">Total Gross</p>
            <p class="text-2xl font-bold text-gray-800">₹{{ number_format($stats['total_gross'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md">
            <p class="text-sm text-gray-500">Total LOP Deduction</p>
            <p class="text-2xl font-bold text-red-600">₹{{ number_format($stats['total_lop'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md">
            <p class="text-sm text-gray-500">Total Net Pay</p>
            <p class="text-2xl font-bold text-green-600">₹{{ number_format($stats['total_net'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">LOP Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">LOP Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($records as $record)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->employee->first_name }} {{ $record->employee->last_name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::create()->month($record->payroll_month)->format('M') }} {{ $record->payroll_year }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₹{{ number_format($record->monthly_salary, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->total_lop_days }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₹{{ number_format($record->lop_deduction_amount, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">₹{{ number_format($record->net_salary, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">{{ $record->status }} / {{ $record->payment_status }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $records->links() }}</div>
    </div>
</div>
@endsection