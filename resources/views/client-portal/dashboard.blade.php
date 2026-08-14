
@extends('layouts.app')

@section('title', 'Client Portal')
@section('page-title', 'Client Portal Overview')

@section('content')
<div class="space-y-6">

    <!-- Client Organization Banner -->
    <div class="relative overflow-hidden bg-slate-900 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg ring-1 ring-slate-900/10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <!-- Decorative background blur -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
        
        <div class="relative space-y-2">
            <span class="inline-flex items-center gap-1.5 text-[11px] uppercase font-semibold tracking-widest text-indigo-200 bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                Client Portal
            </span>
            <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                {{ $client?->company_name ?? 'Client Account' }}
            </h2>
            <p class="text-sm text-slate-200 flex items-center flex-wrap gap-x-2">
                <span>Company Code:</span> 
                <strong class="font-mono text-indigo-100 bg-slate-800/80 px-2 py-0.5 rounded-md border border-slate-700">{{ $client?->company_code ?? '—' }}</strong>
                <span class="text-slate-500">•</span>
                <span class="text-slate-300">Read-only deliverable & milestone tracking</span>
            </p>
        </div>

        <div class="relative flex items-center gap-2.5 bg-slate-800/50 backdrop-blur-sm px-4 py-2.5 rounded-xl border border-slate-700 text-xs shadow-sm">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-slate-200">Account Status: <strong class="text-white">{{ $client?->status?->label() ?? 'Active' }}</strong></span>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Permitted Projects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Permitted</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ $stats['total_projects'] }}</div>
            <div class="text-[11px] text-slate-600 mt-1 font-medium">Total Projects</div>
        </div>

        <!-- Active Projects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-blue-600 uppercase tracking-widest">Active</span>
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div class="text-3xl font-extrabold text-blue-600">{{ $stats['active_projects'] }}</div>
            <div class="text-[11px] text-slate-600 mt-1 font-medium">Currently In Progress</div>
        </div>

        <!-- Milestones Completed -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-widest">Milestones</span>
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-3xl font-extrabold text-emerald-600 flex items-baseline gap-1">
                {{ $stats['completed_milestones'] }}
                <span class="text-base text-slate-500 font-medium">/ {{ $stats['total_milestones'] }}</span>
            </div>
            <div class="text-[11px] text-slate-600 mt-1 font-medium">Phases Completed</div>
        </div>

        <!-- Shared Documents -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-widest">Documents</span>
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <div class="text-3xl font-extrabold text-indigo-600">{{ $stats['shared_documents'] }}</div>
            <div class="text-[11px] text-slate-600 mt-1 font-medium">Files Shared</div>
        </div>
    </div>

    <!-- Client Projects Grid -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Your Active Projects</h3>
                <p class="text-xs text-slate-600 mt-0.5">Track deliverables, overall progress percentage, and milestone schedules</p>
            </div>
            <span class="text-[11px] font-bold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">
                {{ $projects->count() }} Permitted
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Project Name</th>
                        <th class="px-6 py-4">Code</th>
                        <th class="px-6 py-4">Timeline</th>
                        <th class="px-6 py-4">Overall Progress</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($projects as $project)
                        @php $progress = $project->progressPercentage(); @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <a href="{{ route('client-portal.projects.show', $project) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors block">
                                    {{ $project->name }}
                                </a>
                                @if($project->description)
                                    <div class="text-xs text-slate-500 font-normal line-clamp-1 mt-1 max-w-xs">{{ $project->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-600 tracking-tight">{{ $project->code }}</td>
                            <td class="px-6 py-4">
                                <span class="text-slate-800 font-medium">{{ $project->start_date?->format('M d, Y') ?? '—' }}</span>
                                <span class="text-slate-500 block text-[11px] mt-0.5">Due: {{ $project->deadline?->format('M d, Y') ?? 'TBD' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-40 space-y-1.5">
                                    <div class="flex justify-between text-[11px] font-bold">
                                        <span class="text-slate-800">{{ $progress }}%</span>
                                        <span class="text-slate-500 font-medium">{{ $project->milestones->where('status', 'completed')->count() }}/{{ $project->milestones->count() }} phases</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border {{ $project->status?->badgeClass() }}">
                                    {{ $project->status?->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('client-portal.projects.show', $project) }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 transition-colors px-3 py-1.5 rounded-lg">
                                    View Workspace
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <span class="text-sm font-medium text-slate-600">No projects currently shared with your client organization.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Shared Documents Section Preview -->
    @if($sharedDocuments->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Recent Shared Documents</h3>
                    <p class="text-xs text-slate-600 mt-0.5">Deliverables and project agreements published to your account</p>
                </div>
                <a href="{{ route('client-portal.documents.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                    View All 
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($sharedDocuments->take(6) as $doc)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-3 text-xs transition-all duration-200 hover:border-indigo-200 hover:bg-indigo-50/40 hover:shadow-sm">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <!-- Content -->
                        <div class="flex-1 min-w-0 space-y-0.5">
                            <span class="font-bold text-slate-900 block truncate">{{ $doc->title }}</span>
                            <span class="text-[10px] text-slate-500 font-mono truncate block">{{ $doc->file_name }} • {{ $doc->formattedSize() }}</span>
                        </div>
                        <!-- Action -->
                        <a href="{{ route('client-portal.documents.download', $doc) }}" class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors shrink-0" title="Download">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection