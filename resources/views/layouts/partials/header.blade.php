@php
    $user = Auth::user();
    $role = $user?->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user?->role;
@endphp

<header class="h-16 bg-white/95 backdrop-blur border-b border-slate-200/80 px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
    <!-- Left: Title or Breadcrumbs -->
    <div class="flex items-center gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-800 tracking-tight leading-tight">
                @yield('page-title', 'HRM Portal')
            </h2>
            <p class="text-xs text-slate-500 font-medium">
                {{ now()->format('l, F j, Y') }} &bull; Approved Office Network Check
            </p>
        </div>
    </div>

    <!-- Right: Status / Profile -->
    <div class="flex items-center gap-3">
        <!-- Role Indicator Pill -->
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
            @if($role === 'super_admin') bg-purple-50 text-purple-700 border border-purple-200
            @elseif($role === 'hr_admin') bg-blue-50 text-blue-700 border border-blue-200
            @else bg-emerald-50 text-emerald-700 border border-emerald-200 @endif">
            <span class="h-2 w-2 rounded-full @if($role === 'super_admin') bg-purple-500 @elseif($role === 'hr_admin') bg-blue-500 @else bg-emerald-500 @endif animate-pulse"></span>
            @if($role === 'super_admin') Super Admin @elseif($role === 'hr_admin') HR Admin @elseif($role === 'client') Client @elseif($role === 'manager') Manager @elseif($role === 'team_lead') Team Lead @else Employee @endif
        </span>

        <!-- Notifications Link & Unread Counter -->
        @php
            $unreadCount = $user ? \App\Models\Notification::where('user_id', $user->id)->unread()->count() : 0;
        @endphp
        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Notifications">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-1 right-1 h-4 w-4 rounded-full bg-rose-500 text-white font-bold text-[10px] flex items-center justify-center animate-pulse">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </a>

        <!-- Quick Time Clock & Punch Button for Non-Admin Staff -->
        @php
            $isNonAdminStaff = $user && !in_array($role, ['super_admin', 'hr_admin', 'client']);
            $headerEmployee = $isNonAdminStaff ? \App\Models\Employee::where('user_id', $user->id)->first() : null;
            $headerAttendance = $headerEmployee ? \App\Models\AttendanceRecord::where('employee_id', $headerEmployee->id)->whereDate('attendance_date', now()->toDateString())->first() : null;
        @endphp

        @if($isNonAdminStaff)
            <div class="flex items-center gap-2">
                @if(!$headerAttendance || !$headerAttendance->punch_in)
                    <form method="POST" action="{{ route('employee.attendance.punch-in') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            Punch In
                        </button>
                    </form>
                @elseif(!$headerAttendance->punch_out)
                    <form method="POST" action="{{ route('employee.attendance.punch-out') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Punch Out
                        </button>
                    </form>
                @else
                    <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Punched Out ({{ $headerAttendance->total_hours }}h)
                    </span>
                @endif
            </div>
        @endif

        <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-lg bg-slate-100/80 text-xs font-mono font-medium text-slate-700">
            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="header-live-clock">{{ now()->format('H:i') }}</span>
        </div>

        <!-- User Dropdown Mini Card -->
        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
            <div class="h-8 w-8 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
            </div>
            <div class="hidden md:block text-left">
                <p class="text-xs font-bold text-slate-800 leading-tight">{{ $user?->name ?? 'User' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ $user?->username ?? 'user' }}</p>
            </div>
        </div>
    </div>
</header>
