@extends('layouts.app')

@section('title', 'Notification Preferences | HRM')
@section('page-title', 'Notification Preferences')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header with Navigation Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notification Preferences</h1>
            <p class="text-xs text-slate-500 mt-1">Configure your delivery channels across task, timesheet, project, and summary categories.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                &larr; Notification Center
            </a>
            <a href="{{ route('notifications.dispatches') }}" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl transition">
                Dispatch Logs
            </a>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Preferences Matrix Form -->
    <form method="POST" action="{{ route('notifications.preferences.update') }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-900">Delivery Channels Matrix</h3>
                <p class="text-xs text-slate-500 mt-0.5">Toggle channel delivery (In-App, Email, Web-Push) for each category. Security alerts are mandatory and cannot be disabled.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Notification Category</th>
                            @foreach($channels as $chanKey => $chanName)
                                <th class="py-3.5 px-6 text-center">{{ $chanName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($categories as $catKey => $catInfo)
                            <tr class="hover:bg-slate-50/60 transition {{ $catInfo['mandatory'] ? 'bg-slate-50/30' : '' }}">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900">{{ $catInfo['name'] }}</span>
                                        @if($catInfo['mandatory'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                Mandatory
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $catInfo['description'] }}</p>
                                </td>

                                @foreach($channels as $chanKey => $chanName)
                                    <td class="py-4 px-6 text-center">
                                        @if($catInfo['mandatory'])
                                            <input type="checkbox" checked disabled class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-not-allowed opacity-60">
                                        @else
                                            <input type="hidden" name="preferences[{{ $catKey }}][{{ $chanKey }}]" value="0">
                                            <input type="checkbox" 
                                                   name="preferences[{{ $catKey }}][{{ $chanKey }}]" 
                                                   value="1"
                                                   {{ !empty($matrix[$catKey][$chanKey]) ? 'checked' : '' }}
                                                   class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <span class="text-xs text-slate-500">Security notifications will always be delivered to all available channels for account safety.</span>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                    Save Preferences
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
