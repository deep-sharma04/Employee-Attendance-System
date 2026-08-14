@extends('layouts.app')

@section('title', 'Project Health Threshold Settings')
@section('page-title', 'System Settings: Project Health Engine')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Health Engine Configuration</h2>
            <p class="text-xs text-slate-500 mt-0.5">Configure deterministic thresholds for Schedule Variance and Overdue Milestones without code modifications</p>
        </div>
        <a href="{{ route('super-admin.settings.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; System Settings
        </a>
    </div>

    <!-- Health Engine Logic Info Card -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 space-y-2">
        <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            How the Health Engine Evaluates Projects
        </h3>
        <p class="text-xs text-indigo-700 leading-relaxed">
            The Project Health Engine deterministically calculates whether an active project is <strong>Good</strong>, <strong>At Risk</strong>, or <strong>Critical</strong> based on two core factors:
            <strong>Schedule Variance</strong> (Expected Progress % based on days elapsed vs Actual Milestone Completion %) and <strong>Overdue Milestones</strong>.
        </p>
    </div>

    <form method="POST" action="{{ route('super-admin.settings.project-health.update') }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf

        <!-- Schedule Variance Thresholds -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Schedule Variance Thresholds (%)</h3>
            <p class="text-xs text-slate-500">Difference between expected percentage completion and actual milestones completed percentage.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="project_health_schedule_variance_at_risk" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        At Risk Threshold (%) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" id="project_health_schedule_variance_at_risk" name="project_health_schedule_variance_at_risk"
                            value="{{ old('project_health_schedule_variance_at_risk', $thresholds['schedule_variance_at_risk']) }}" required min="1" max="100"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 pr-8">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">%</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Variance &ge; this value flags project as <strong>At Risk</strong></p>
                    @error('project_health_schedule_variance_at_risk')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="project_health_schedule_variance_critical" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Critical Threshold (%) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" id="project_health_schedule_variance_critical" name="project_health_schedule_variance_critical"
                            value="{{ old('project_health_schedule_variance_critical', $thresholds['schedule_variance_critical']) }}" required min="1" max="100"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 pr-8">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">%</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Variance &ge; this value flags project as <strong>Critical</strong></p>
                    @error('project_health_schedule_variance_critical')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Overdue Milestones Count Thresholds -->
        <div class="space-y-4 pt-4 border-t border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Overdue Milestone Thresholds (Count)</h3>
            <p class="text-xs text-slate-500">Number of past-due, non-completed milestones that trigger health degradation.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="project_health_overdue_milestones_at_risk" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        At Risk Overdue Count <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="project_health_overdue_milestones_at_risk" name="project_health_overdue_milestones_at_risk"
                        value="{{ old('project_health_overdue_milestones_at_risk', $thresholds['overdue_milestones_at_risk']) }}" required min="1" max="50"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <p class="text-[11px] text-slate-400 mt-1">Count &ge; this triggers <strong>At Risk</strong></p>
                    @error('project_health_overdue_milestones_at_risk')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="project_health_overdue_milestones_critical" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Critical Overdue Count <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="project_health_overdue_milestones_critical" name="project_health_overdue_milestones_critical"
                        value="{{ old('project_health_overdue_milestones_critical', $thresholds['overdue_milestones_critical']) }}" required min="1" max="50"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <p class="text-[11px] text-slate-400 mt-1">Count &ge; this triggers <strong>Critical</strong></p>
                    @error('project_health_overdue_milestones_critical')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Save & Apply Thresholds
            </button>
        </div>
    </form>
</div>
@endsection
