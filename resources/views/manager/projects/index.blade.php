@extends('layouts.app')

@section('title', 'Projects Management')
@section('page-title', 'Projects Portfolio')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Portfolio & Delivery</h2>
            <p class="text-xs text-slate-500 mt-0.5">Track timelines, budgets, squads, deliverables, and deterministic health status</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('manager.projects.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                New Project
            </a>
        </div>
    </div>

    <!-- Portfolio KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Projects</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active</span>
            <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">At Risk</span>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['at_risk'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Critical</span>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['critical'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs col-span-2 md:col-span-1">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Completed</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['completed'] }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.projects.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by project name or code..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\ProjectStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Health Filter -->
            <div>
                <select name="health" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Healths</option>
                    @foreach(\App\Enums\ProjectHealth::cases() as $hl)
                        <option value="{{ $hl->value }}" {{ request('health') === $hl->value ? 'selected' : '' }}>{{ $hl->label() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <select name="priority" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Priorities</option>
                    @foreach(\App\Enums\ProjectPriority::cases() as $pr)
                        <option value="{{ $pr->value }}" {{ request('priority') === $pr->value ? 'selected' : '' }}>{{ $pr->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'health', 'priority', 'client_id', 'team_id']))
                    <a href="{{ route('manager.projects.index') }}" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($projects as $project)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">
                <!-- Top Header -->
                <div class="p-5 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-mono font-semibold uppercase tracking-wider text-slate-400">{{ $project->code }}</span>
                            <h3 class="font-bold text-slate-900 text-base leading-snug">
                                <a href="{{ route('manager.projects.show', $project) }}" class="hover:text-indigo-600 transition-colors">
                                    {{ $project->name }}
                                </a>
                            </h3>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $project->health?->badgeClass() }}">
                            {{ $project->health?->label() }}
                        </span>
                    </div>

                    <!-- Client & Team Badge -->
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        @if($project->client)
                            <span class="font-medium text-slate-700">{{ $project->client->company_name }}</span>
                        @else
                            <span class="italic text-slate-400">Internal Project</span>
                        @endif
                        @if($project->team)
                            <span>&bull;</span>
                            <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-semibold">
                                {{ $project->team->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Status & Priority badges -->
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->status?->badgeClass() }}">
                            {{ $project->status?->label() }}
                        </span>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->priority?->badgeClass() }}">
                            {{ $project->priority?->label() }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    @php $progress = $project->progressPercentage(); @endphp
                    <div class="space-y-1 pt-1">
                        <div class="flex justify-between text-[11px] font-semibold">
                            <span class="text-slate-500">Milestones Progress</span>
                            <span class="text-slate-900">{{ $progress }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                </div>

                               <!-- Footer Card Info -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        <span class="text-slate-400">Deadline:</span>
                        <strong class="{{ $project->isPastDeadline() ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $project->deadline?->format('M d, Y') ?? 'None' }}
                        </strong>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Link to the specific Project's Documents -->
                        <a href="{{ route('manager.projects.documents.index', $project) }}" class="font-semibold text-slate-500 hover:text-indigo-600 transition-colors">
                            Documents
                        </a>
                        <a href="{{ route('manager.projects.show', $project) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                            View Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-400 text-sm">
                <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                <h3 class="text-base font-bold text-slate-900 mt-3">No Projects Found</h3>
                <p class="text-xs text-slate-500 mt-1">Get started by creating your first client or internal project.</p>
                <div class="mt-4">
                    <a href="{{ route('manager.projects.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition-colors">
                        Create Project
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($projects->hasPages())
        <div class="pt-4">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
