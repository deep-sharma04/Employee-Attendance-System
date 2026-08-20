@extends('layouts.app')

@section('title', 'Manager Dashboard')
@section('page-title', 'Manager Workspace Overview')

@section('content')
<div class="space-y-6">
    @php
        $mgrEmployee = \App\Models\Employee::where('user_id', Auth::id())->first();
        $mgrAttendance = $mgrEmployee ? \App\Models\AttendanceRecord::where('employee_id', $mgrEmployee->id)->whereDate('attendance_date', now()->toDateString())->first() : null;
    @endphp
    <!-- Daily Attendance Punch Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Manager Shift Punch &bull; {{ now()->format('l, F j') }}
                </div>
                <h3 class="text-xl font-bold mt-2">Daily Attendance Punch</h3>
                <p class="text-xs text-slate-300 mt-1 max-w-md">
                    @if(!$mgrAttendance || !$mgrAttendance->punch_in)
                        Record your shift punch in from the office network. Late arrivals beyond 15 mins count as Late.
                    @elseif(!$mgrAttendance->punch_out)
                        You punched in at <strong class="text-white">{{ substr($mgrAttendance->punch_in, 0, 5) }}</strong>. Remember to punch out before leaving.
                    @else
                        Shift completed! Punched in at <strong class="text-white">{{ substr($mgrAttendance->punch_in, 0, 5) }}</strong> and out at <strong class="text-white">{{ substr($mgrAttendance->punch_out, 0, 5) }}</strong> (Total: {{ $mgrAttendance->total_hours }} hrs).
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if(!$mgrAttendance || !$mgrAttendance->punch_in)
                    <form method="POST" action="{{ route('employee.attendance.punch-in') }}">
                        @csrf
                        <button type="submit" class="px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 transition-all flex items-center gap-2 cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            Punch In Now
                        </button>
                    </form>
                @elseif(!$mgrAttendance->punch_out)
                    <form method="POST" action="{{ route('employee.attendance.punch-out') }}">
                        @csrf
                        <button type="submit" class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-white font-bold text-sm shadow-lg shadow-amber-500/30 transition-all flex items-center gap-2 cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Punch Out (End Shift)
                        </button>
                    </form>
                @else
                    <div class="px-5 py-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Day Completed ({{ $mgrAttendance->total_hours }}h)
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Projects</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $activeProjectsCount }}</h3>
                <span class="text-[11px] font-medium text-indigo-600">Total: {{ $projects->count() }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Managed Teams</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $totalTeamsCount }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Active Units</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Clients</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $totalClientsCount }}</h3>
                <span class="text-[11px] font-medium text-blue-600">Client Accounts</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role Authority</p>
                <h3 class="text-sm font-bold text-purple-700 mt-1">Project Manager</h3>
                <span class="text-[11px] font-medium text-slate-500">Super Admin &rarr; Manager</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
        </div>
    </div>

    <!-- Managed Projects Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Projects in Scope</h3>
                <p class="text-xs text-slate-500 mt-0.5">Overview of assigned project portfolios and health statuses</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Project</th>
                        <th class="px-6 py-3.5">Client</th>
                        <th class="px-6 py-3.5">Team</th>
                        <th class="px-6 py-3.5">Deadline</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Health</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <div>{{ $project->name }}</div>
                                <div class="text-xs text-slate-400 font-normal font-mono">{{ $project->code }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $project->client?->company_name ?? '—' }}</td>
                            <td class="px-6 py-4">{{ $project->team?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-mono">{{ $project->deadline?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $project->status?->badgeClass() }}">
                                    {{ $project->status?->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $project->health?->badgeClass() }}">
                                    {{ $project->health?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No projects assigned yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
