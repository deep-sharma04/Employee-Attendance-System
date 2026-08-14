@extends('layouts.app')

@section('title', 'In-App Notifications | HRM')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifications Center</h1>
            <p class="text-sm text-slate-500 mt-1">Real-time alerts for leave approvals, payslip releases, and document verification.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.preferences') }}" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2.5 rounded-xl transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Preferences
            </a>
            <a href="{{ route('notifications.dispatches') }}" class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold px-3.5 py-2.5 rounded-xl transition-colors">
                Dispatch Logs
            </a>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs divide-y divide-slate-100 overflow-hidden">
        @forelse($notifications as $notification)
        <div class="p-5 flex items-start gap-4 transition-colors {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-700' }}">
                @if(str_contains($notification->type, 'leave'))
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                @elseif(str_contains($notification->type, 'payslip'))
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @elseif(str_contains($notification->type, 'document'))
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        {{ $notification->title }}
                        @if(!$notification->read_at)
                            <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
                        @endif
                    </h3>
                    <span class="text-[11px] text-slate-400 whitespace-nowrap">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $notification->message }}</p>

                <div class="mt-3 flex items-center gap-3">
                    @if(isset($notification->data['url']))
                        <a href="{{ route('notifications.read', $notification->id) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900">
                            View Details &rarr;
                        </a>
                    @endif
                    @if(!$notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                                Mark as read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-slate-500">
            <svg class="h-10 w-10 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <p class="text-xs font-medium">You have no notifications yet.</p>
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
