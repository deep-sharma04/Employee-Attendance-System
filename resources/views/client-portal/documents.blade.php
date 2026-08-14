@extends('layouts.app')

@section('title', 'Shared Documents')
@section('page-title', 'Client Documents Repository')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Shared Project Documents</h2>
            <p class="text-xs text-slate-500 mt-0.5">Agreements, project specifications, architectural designs, and deliverables</p>
        </div>
        <a href="{{ route('client-portal.dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            &larr; Back to Dashboard
        </a>
    </div>

    <!-- Documents Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Document Title</th>
                        <th class="px-6 py-3.5">File Name & Size</th>
                        <th class="px-6 py-3.5">Published Date</th>
                        <th class="px-6 py-3.5">Notes</th>
                        <th class="px-6 py-3.5 text-right">Download</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 text-sm">
                                {{ $doc->title }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-slate-700">{{ $doc->file_name }}</span>
                                <span class="text-slate-400 block text-[10px]">{{ $doc->formattedSize() }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $doc->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs">
                                {{ $doc->notes ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('client-portal.documents.download', $doc) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No documents have been shared with your client organization yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
