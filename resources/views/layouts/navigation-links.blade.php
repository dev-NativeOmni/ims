@php
    $routeIs = fn($patterns) => request()->routeIs($patterns);
@endphp

<!-- UTAMA Group -->
<div class="space-y-1">
    <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
        Utama
    </span>
    <a href="{{ route('dashboard') }}" class="{{ $getLinkClasses($routeIs('dashboard') || $routeIs('*.dashboard')) }}">
        <svg class="{{ $getIconClasses($routeIs('dashboard') || $routeIs('*.dashboard')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Dashboard</span>
    </a>
</div>

<!-- DATA MASTER Group -->
@if ($isAdmin)
    <div class="mt-6 space-y-1">
        <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
            Data Master
        </span>

        @if ($hasRoute('programs.index'))
            <a href="{{ route('programs.index') }}" class="{{ $getLinkClasses($routeIs('programs.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('programs.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                </svg>
                <span>Program</span>
            </a>
        @endif

        @if ($hasRoute('class-rooms.index'))
            <a href="{{ route('class-rooms.index') }}" class="{{ $getLinkClasses($routeIs('class-rooms.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('class-rooms.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Kelas</span>
            </a>
        @endif

        @if ($hasRoute('teachers.index'))
            <a href="{{ route('teachers.index') }}" class="{{ $getLinkClasses($routeIs('teachers.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('teachers.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Guru</span>
            </a>
        @endif

        @if ($hasRoute('parents.index'))
            <a href="{{ route('parents.index') }}" class="{{ $getLinkClasses($routeIs('parents.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('parents.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>Orangtua</span>
            </a>
        @endif

        @if ($hasRoute('students.index'))
            <a href="{{ route('students.index') }}" class="{{ $getLinkClasses($routeIs('students.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('students.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Santri</span>
            </a>
        @endif
    </div>
@endif

<!-- AKADEMIK & TAHFIZH Group -->
@if ($canManageRecords || ($canViewProgress && $hasRoute('progress.index')) || ($canViewReports && $hasRoute('reports.index')))
    <div class="mt-6 space-y-1">
        <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
            Tahfizh
        </span>

        @if ($canManageRecords)
            @if ($hasRoute('quick-inputs.index'))
                <a href="{{ route('quick-inputs.index') }}" class="{{ $getLinkClasses($routeIs('quick-inputs.*')) }}">
                    <svg class="{{ $getIconClasses($routeIs('quick-inputs.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Input Cepat</span>
                </a>
            @endif

            @if ($hasRoute('hafalan-records.index'))
                <a href="{{ route('hafalan-records.index') }}" class="{{ $getLinkClasses($routeIs('hafalan-records.*')) }}">
                    <svg class="{{ $getIconClasses($routeIs('hafalan-records.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Hafalan</span>
                </a>
            @endif

            @if ($hasRoute('murajaah-records.index'))
                <a href="{{ route('murajaah-records.index') }}" class="{{ $getLinkClasses($routeIs('murajaah-records.*')) }}">
                    <svg class="{{ $getIconClasses($routeIs('murajaah-records.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                    </svg>
                    <span>Murajaah</span>
                </a>
            @endif

            @if ($hasRoute('hafalan-targets.index'))
                <a href="{{ route('hafalan-targets.index') }}" class="{{ $getLinkClasses($routeIs('hafalan-targets.*')) }}">
                    <svg class="{{ $getIconClasses($routeIs('hafalan-targets.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <span>Target</span>
                </a>
            @endif
        @endif

        <a href="{{ route('quran.mushaf') }}" class="{{ $getLinkClasses($routeIs('quran.mushaf')) }}">
            <svg class="{{ $getIconClasses($routeIs('quran.mushaf')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>Mushaf Al-Qur'an</span>
        </a>

        @if ($canViewProgress && $hasRoute('progress.index'))
            <a href="{{ route('progress.index') }}" class="{{ $getLinkClasses($routeIs('progress.*')) }}">
                <svg class="{{ $getIconClasses($routeIs('progress.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Progress</span>
            </a>
        @endif

        @if ($canViewReports && $hasRoute('reports.index'))
            <a href="{{ route('reports.index') }}" class="{{ $getLinkClasses($routeIs('reports.*') && ! $routeIs('reports.teachers')) }}">
                <svg class="{{ $getIconClasses($routeIs('reports.*') && ! $routeIs('reports.teachers')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Laporan</span>
            </a>
        @endif

        @if (($isSuperAdmin || $isAdminUser || $isHeadmaster) && $hasRoute('reports.teachers'))
            <a href="{{ route('reports.teachers') }}" class="{{ $getLinkClasses($routeIs('reports.teachers')) }}">
                <svg class="{{ $getIconClasses($routeIs('reports.teachers')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Kinerja Guru</span>
            </a>
        @endif
    </div>
@endif

<!-- KEAGAMAAN Group -->
@if ($hasRoute('adab.index'))
    <div class="mt-6 space-y-1">
        <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
            Keagamaan
        </span>

        <a href="{{ route('adab.index') }}" class="{{ $getLinkClasses($routeIs('adab.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('adab.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Adab</span>
        </a>
    </div>
@endif

<!-- KETAHANAN SEKOLAH Group -->
@if ($hasRoute('student-points.index'))
    <div class="mt-6 space-y-1">
        <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
            Ketahanan Sekolah
        </span>

        <a href="{{ route('student-points.index') }}" class="{{ $getLinkClasses($routeIs('student-points.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('student-points.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Poin & Disiplin</span>
        </a>
    </div>
@endif

<!-- SISTEM Group -->
<div class="mt-6 space-y-1">
    <span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
        Sistem
    </span>

    @if ($isSuperAdmin && $hasRoute('users.index'))
        <a href="{{ route('users.index') }}" class="{{ $getLinkClasses($routeIs('users.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('users.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Manajemen User</span>
        </a>
    @endif

    @if ($hasRoute('system-notifications.index'))
        <a href="{{ route('system-notifications.index') }}" class="{{ $getLinkClasses($routeIs('system-notifications.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('system-notifications.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="flex-1 flex justify-between items-center">
                <span>Notifikasi</span>
                @if ($unreadNotificationCount > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                    </span>
                @endif
            </span>
        </a>
    @endif

    @if ($canViewAudit && $hasRoute('audit-logs.index'))
        <a href="{{ route('audit-logs.index') }}" class="{{ $getLinkClasses($routeIs('audit-logs.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('audit-logs.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Audit</span>
        </a>
    @endif

    @if ($isSuperAdmin && $hasRoute('settings.index'))
        <a href="{{ route('settings.index') }}" class="{{ $getLinkClasses($routeIs('settings.*')) }}">
            <svg class="{{ $getIconClasses($routeIs('settings.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Pengaturan</span>
        </a>
    @endif
</div>
