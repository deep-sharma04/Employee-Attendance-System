@extends('layouts.app')

@section('title', 'Project Documents - ' . $project->name)
@section('page-title', 'Documents — ' . $project->name)

@section('content')
<div class="space-y-6">
    <!-- Header & Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <a href="{{ route('manager.projects.index') }}" class="hover:text-indigo-600 transition-colors">Projects</a>
                <span>&bull;</span>
                <a href="{{ route('manager.projects.show', $project) }}" class="hover:text-indigo-600 transition-colors">{{ $project->code }}</a>
                <span>&bull;</span>
                <span class="text-indigo-600">Documents & Knowledge</span>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900 mt-1">Project Documents & File Assets</h2>
            <p class="text-xs text-slate-500">Manage versioned documentation, specifications, client assets, and knowledge artifacts.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('manager.projects.show', $project) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Project
            </a>
            <a href="{{ route('manager.knowledge.index', ['project_id' => $project->id]) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                Search Knowledge
            </a>
        </div>
    </div>

    <!-- Upload Document Form Card -->
    @can('uploadDocument', $project)
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Upload / Update Document
            </h3>
            <span class="text-[11px] text-slate-400 font-medium">Re-uploading with identical name creates a new version (up to 10 retained)</span>
        </div>

        <form method="POST" action="{{ route('manager.projects.documents.store', $project) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Document Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required class="mt-1.5 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. Architecture_Specification.pdf" value="{{ old('name') }}">
                <p class="text-[11px] text-slate-400 mt-1">Use a clear identifier. Matching an existing name automatically increments the version.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">File Asset <span class="text-rose-500">*</span> (Max 2MB)</label>
                <input type="file" name="file" required class="mt-1.5 block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl shadow-xs cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1">Allowed: PDF, DOC, DOCX, XLS, XLSX, PNG, JPEG.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Description / Change Notes</label>
                <textarea name="description" rows="2" class="mt-1.5 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500" placeholder="Summary of contents, revisions, or usage instructions...">{{ old('description') }}</textarea>
            </div>

            @can('manageDocuments', $project)
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="is_client_visible" id="is_client_visible" value="1" class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_client_visible') ? 'checked' : '' }}>
                <label for="is_client_visible" class="text-xs font-semibold text-slate-700 select-none cursor-pointer">
                    Share with Client (Make visible in Client Portal repository)
                </label>
            </div>
            @endcan

            <div class="md:col-span-2 pt-2 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    Upload Document Asset
                </button>
            </div>
        </form>
    </div>
    @endcan

    <!-- Document Repository & Search List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-0">
        <!-- Search & Filter Header -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="GET" action="{{ route('manager.projects.documents.index', $project) }}" class="w-full sm:max-w-md flex gap-2">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or description..." class="w-full text-xs pl-9 pr-4 py-2 border-slate-300 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
                    <svg class="h-4 w-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-colors">
                    Filter
                </button>
                @if(request('search'))
                    <a href="{{ route('manager.projects.documents.index', $project) }}" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                        Clear
                    </a>
                @endif
            </form>

            <div class="text-xs text-slate-500 font-medium">
                Showing {{ $documents->total() }} total {{ Str::plural('document', $documents->total()) }}
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Document Details</th>
                        <th class="px-6 py-3.5">Version & History</th>
                        <th class="px-6 py-3.5">Visibility</th>
                        <th class="px-6 py-3.5">Uploader & Date</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($documents as $doc)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shrink-0 mt-0.5">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $doc->name }}</div>
                                        @if($doc->description)
                                            <p class="text-xs text-slate-500 mt-0.5 max-w-md">{{ $doc->description }}</p>
                                        @endif
                                        @if($doc->latestVersion)
                                            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-2">
                                                <span>{{ $doc->latestVersion->formattedFileSize() }}</span>
                                                <span>&bull;</span>
                                                <span>{{ $doc->latestVersion->mime_type }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                        v{{ $doc->current_version }} (Latest)
                                    </span>
                                    
                                    @if($doc->versions->count() > 1)
                                        <details class="text-[11px] text-slate-500 cursor-pointer">
                                            <summary class="hover:text-indigo-600 font-semibold select-none">
                                                {{ $doc->versions->count() }} versions available
                                            </summary>
                                            <div class="mt-2 space-y-1 pl-2 border-l-2 border-slate-200">
                                                @foreach($doc->versions as $v)
                                                    <div class="flex items-center justify-between gap-3 py-0.5">
                                                        <span>v{{ $v->version_number }} ({{ $v->created_at->format('M d, Y') }})</span>
                                                        <a href="{{ route('manager.projects.documents.download', [$project, $doc, $v->version_number]) }}" class="text-indigo-600 hover:underline font-bold">
                                                            Download
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if($doc->is_client_visible)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        Shared with Client
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        Internal Only
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $doc->uploader?->name ?? 'System' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $doc->created_at->format('M d, Y · h:i A') }}</div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('manager.projects.documents.download', [$project, $doc]) }}" class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold transition-colors inline-flex items-center gap-1" title="Download Latest Version">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        Download
                                    </a>

                                    @can('manageDocuments', $project)
                                        <form method="POST" action="{{ route('manager.projects.documents.toggle-share', [$project, $doc]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition-colors text-[11px]" title="Toggle Client Visibility">
                                                {{ $doc->is_client_visible ? 'Make Internal' : 'Share Client' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('manager.projects.documents.destroy', [$project, $doc]) }}" onsubmit="return confirm('Are you sure you want to delete document \'{{ $doc->name }}\' and all its versions?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold transition-colors" title="Delete Document">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="h-10 w-10 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <div class="font-bold text-slate-600 text-sm">No project documents found</div>
                                    <p class="text-xs text-slate-400">Upload documentation, specifications, or contracts to begin managing knowledge assets for this project.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="p-4 border-t border-slate-200">{{ $documents->links() }}</div>
        @endif
    </div>
</div>
@endsection