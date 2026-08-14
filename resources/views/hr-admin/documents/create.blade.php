@extends('layouts.app')

@section('title', 'Upload Employee Document')
@section('page-title', 'Upload Employee Verification Document')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Upload Verification Document</h2>
            <p class="text-xs text-slate-500">Securely store identity, education, or employment documents for active workforce.</p>
        </div>
        <a href="{{ route('hr-admin.documents.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
            &larr; Back to Documents
        </a>
    </div>

    <!-- Security & Specification Notice -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-start gap-3">
        <svg class="h-5 w-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-xs text-indigo-900 space-y-1">
            <p class="font-bold">Upload Guidelines & Storage Policy:</p>
            <ul class="list-disc list-inside space-y-0.5 text-indigo-800">
                <li>Allowed file formats: <span class="font-semibold">PDF, PNG, JPEG, JPG</span>.</li>
                <li>Maximum file size: <span class="font-semibold">500 KB</span> per file.</li>
                <li>Files are stored securely outside the public webroot with authenticated role-based access control.</li>
            </ul>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <form method="POST" action="{{ route('hr-admin.documents.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- Employee Selection -->
            <div>
                <label for="employee_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Target Employee <span class="text-rose-500">*</span>
                </label>
                <select name="employee_id" id="employee_id" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee_id') border-rose-400 bg-rose-50/20 @enderror">
                    <option value="">Select Employee...</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ (old('employee_id', $selectedEmployeeId) == $emp->id) ? 'selected' : '' }}>
                            {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code }}) — {{ $emp->department }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <p class="text-rose-500 text-[11px] mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Type Selection -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="document_type_id" class="block text-xs font-semibold text-slate-700">
                        Document Classification Type <span class="text-rose-500">*</span>
                    </label>
                    <a href="{{ route('hr-admin.documents.types') }}" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-medium">
                        + Manage Types
                    </a>
                </div>
                <select name="document_type_id" id="document_type_id" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('document_type_id') border-rose-400 bg-rose-50/20 @enderror">
                    <option value="">Select Document Type...</option>
                    @foreach($documentTypes as $type)
                        <option value="{{ $type->id }}" {{ old('document_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }} {{ $type->is_mandatory ? '(Mandatory)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('document_type_id')
                    <p class="text-rose-500 text-[11px] mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Title -->
            <div>
                <label for="title" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Document Title / Description <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" required value="{{ old('title') }}" placeholder="e.g. National ID Copy 2026 / Master Degree Certificate"
                    class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('title') border-rose-400 bg-rose-50/20 @enderror">
                @error('title')
                    <p class="text-rose-500 text-[11px] mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- File Upload Dropzone/Input -->
            <div>
                <label for="document_file" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Document File Attachment <span class="text-rose-500">*</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-indigo-400 rounded-2xl p-6 text-center transition-colors">
                    <svg class="h-10 w-10 text-slate-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <input type="file" name="document_file" id="document_file" required accept=".pdf,.png,.jpg,.jpeg"
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-2">Max allowed file size: 500 KB (PDF, PNG, JPG, JPEG)</p>
                </div>
                @error('document_file')
                    <p class="text-rose-500 text-[11px] mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Submit Actions -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('hr-admin.documents.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    Upload & Queue Verification
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
