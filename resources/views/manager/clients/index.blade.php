@extends('layouts.app')

@section('title', 'Client Directory')
@section('page-title', 'Client Management')

@section('content')
<div class="space-y-6">
    <!-- Top Stat Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Clients</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</h3>
                <span class="text-[11px] font-medium text-slate-500">All registered clients</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Clients</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['active'] }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Engaged accounts</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Prospective Leads</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['lead'] }}</h3>
                <span class="text-[11px] font-medium text-blue-600">In pipeline</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inactive Accounts</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['inactive'] }}</h3>
                <span class="text-[11px] font-medium text-slate-500">Dormant / Archived</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </div>
        </div>
    </div>

    <!-- Controls Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('manager.clients.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <svg class="h-4 w-4 absolute left-3.5 top-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company or code..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <select name="status" class="py-2 px-3 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="lead" {{ request('status') === 'lead' ? 'selected' : '' }}>Lead</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('manager.clients.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">Clear</a>
            @endif
        </form>

        <a href="{{ route('manager.clients.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add New Client
        </a>
    </div>

    <!-- Clients Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($clients as $client)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $client->company_code ?? 'NO-CODE' }}</span>
                            <h3 class="font-bold text-slate-900 text-lg hover:text-indigo-600 transition-colors">
                                <a href="{{ route('manager.clients.show', $client) }}">{{ $client->company_name }}</a>
                            </h3>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $client->status?->badgeClass() }}">
                            {{ $client->status?->label() }}
                        </span>
                    </div>

                    @if($client->email || $client->phone)
                        <div class="mt-4 space-y-1.5 text-xs text-slate-500">
                            @if($client->email)
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="truncate">{{ $client->email }}</span>
                                </div>
                            @endif
                            @if($client->phone)
                                <div class="flex items-center gap-2">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <span>{{ $client->phone }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($client->primaryContact)
                        <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2.5">
                            <div class="h-7 w-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($client->primaryContact->name, 0, 1)) }}
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ $client->primaryContact->name }}</p>
                                <p class="text-[11px] text-slate-400 truncate">{{ $client->primaryContact->position ?? 'Primary Contact' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                    <div class="flex items-center gap-3">
                        <span><strong>{{ $client->projects_count }}</strong> Projects</span>
                        <span>&bull;</span>
                        <span><strong>{{ $client->contacts_count }}</strong> Contacts</span>
                    </div>
                    <a href="{{ route('manager.clients.show', $client) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        View &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 border border-slate-200 text-center">
                <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <h3 class="text-base font-bold text-slate-900 mt-3">No clients found</h3>
                <p class="text-xs text-slate-500 mt-1">Get started by creating your first client company profile.</p>
                <a href="{{ route('manager.clients.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                    Add Client
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $clients->links() }}
    </div>
</div>
@endsection
