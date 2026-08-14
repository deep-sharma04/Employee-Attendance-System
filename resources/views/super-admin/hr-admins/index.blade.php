@extends('layouts.app')

@section('title', 'HR Admin Accounts | Super Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">HR Admin Management</h1>
            <p class="text-sm text-slate-500 mt-1">Manage, onboard, configure permissions, and monitor administrative accounts.</p>
        </div>
        <div>
            <a href="{{ route('super-admin.hr-admins.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-xs">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Create HR Admin
            </a>
        </div>
    </div>

    <!-- Temporary Credentials Alert -->
    @if(session('temp_credentials_username'))
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-amber-900">Temporary Account Credentials Generated</h3>
                <p class="text-xs text-amber-700 mt-1">Please record these credentials securely. The user must update their password upon initial login.</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 bg-white/80 p-3 rounded-xl border border-amber-200/80 font-mono text-xs text-slate-800">
                    <div><span class="text-slate-500 font-sans">Username:</span> <strong>{{ session('temp_credentials_username') }}</strong></div>
                    <div><span class="text-slate-500 font-sans">Password:</span> <strong class="text-amber-800 bg-amber-100 px-2 py-0.5 rounded">{{ session('temp_credentials_password') }}</strong></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('super-admin.hr-admins.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Search Keyword</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Username, Email..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Account Status</label>
                <select name="status" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active Only</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Suspended / Disabled</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors shadow-xs">
                    Apply Filters
                </button>
                <a href="{{ route('super-admin.hr-admins.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Accounts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-6 py-3.5">Administrator</th>
                        <th class="px-6 py-3.5">Username</th>
                        <th class="px-6 py-3.5">Email Address</th>
                        <th class="px-6 py-3.5">Account Status</th>
                        <th class="px-6 py-3.5">Created Date</th>
                        <th class="px-6 py-3.5 text-right">Quick Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-normal text-slate-700">
                    @forelse($hrAdmins as $admin)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-3.5 font-medium text-slate-900 flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-900">{{ $admin->name }}</p>
                                <p class="text-[11px] text-slate-400">ID: #{{ $admin->id }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 font-mono text-[11px] text-slate-600">
                            {{ $admin->username }}
                        </td>
                        <td class="px-6 py-3.5 text-slate-600">
                            {{ $admin->email }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            @if($admin->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Suspended
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-slate-500 text-[11px]">
                            {{ $admin->created_at ? $admin->created_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="px-6 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('super-admin.hr-admins.edit', $admin->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs">
                                    Edit
                                </a>
                                <span class="text-slate-300">|</span>
                                <form method="POST" action="{{ route('super-admin.hr-admins.toggle-status', $admin->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-semibold text-xs {{ $admin->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                        {{ $admin->is_active ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            No HR Admin accounts found. Click "Create HR Admin" to add the first account.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hrAdmins instanceof \Illuminate\Pagination\LengthAwarePaginator && $hrAdmins->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $hrAdmins->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
