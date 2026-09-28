{{-- Tab Navigasi Terpadu: Kalender Akademik, Jadwal Pelajaran Kelas, dan Pindah Catatan Setoran --}}
<div class="flex flex-wrap items-center gap-1 border-b border-gray-200 dark:border-zinc-800">
    <a href="{{ route('academic-calendar.index') }}"
       class="px-4 py-2.5 text-sm font-bold border-b-2 transition inline-flex items-center gap-2 {{ request()->routeIs('academic-calendar.*') ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300' }}">
        <x-heroicon-o-calendar-days class="w-4 h-4" />
        <span>Kalender Akademik &amp; Libur</span>
    </a>

    @if (auth()->user()?->hasAnyRole(['super_admin', 'admin']))
        <a href="{{ route('class-schedules.index') }}"
           class="px-4 py-2.5 text-sm font-bold border-b-2 transition inline-flex items-center gap-2 {{ request()->routeIs('class-schedules.index') ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-calendar class="w-4 h-4" />
            <span>Jadwal Pelajaran Kelas</span>
        </a>

        <a href="{{ route('class-schedules.shift') }}"
           class="px-4 py-2.5 text-sm font-bold border-b-2 transition inline-flex items-center gap-2 {{ request()->routeIs('class-schedules.shift*') ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-arrows-right-left class="w-4 h-4" />
            <span>Pindah / Koreksi Jadwal Setoran</span>
        </a>
    @endif
</div>
