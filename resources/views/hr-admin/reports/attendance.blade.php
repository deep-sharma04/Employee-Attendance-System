@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Attendance Reports</h1>

    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <form method="GET" action="{{ route('hr-admin.reports.attendance') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                <label class="block text-xs font-medium text-gray-500">Status</label>
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="present" @selected($status == 'present')>Present</option>
                    <option value="late" @selected($status == 'late')>Late</option>
                    <option value="half_day" @selected($status == 'half_day')>Half Day</option>
                    <option value="absent" @selected($status == 'absent')>Absent</option>
                    <option value="leave" @selected($status == 'leave')>Leave</option>
                </select>
            </div>
            <div class="md:col-span-5 flex justify-end gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-xs transition-colors">Apply Filters</button>
                <a href="{{ route('hr-admin.reports.attendance.export', request()->all()) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Export CSV
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Punch In</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Punch Out</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($records as $record)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->attendance_date)->format('d M Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->employee->first_name }} {{ $record->employee->last_name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->employee->department }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 capitalize">{{ $record->status }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->punch_in_time ? \Carbon\Carbon::parse($record->punch_in_time)->format('h:i A') : '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->punch_out_time ? \Carbon\Carbon::parse($record->punch_out_time)->format('h:i A') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $records->links() }}</div>
    </div>
</div>
@endsection