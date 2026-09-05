<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Dashboard Guru
            </h2>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                Monitoring murid bimbingan, setoran, murajaah, dan target hafalan.
            </p>
        </div>
    </x-slot>

    @php
        $studentsProgress = collect(data_get($stats, 'students_progress', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (! data_get($stats, 'teacher'))
                <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-xl p-4 text-xs sm:text-sm">
                    Akun guru ini belum memiliki profil guru. Hubungi admin.
                </div>
            @endif

            {{-- ═══════════════ 2x2 COMPACT STATS GRID (BENTO MODERN) ═══════════════ --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-sm border border-zinc-200/80 dark:border-white/[0.08] rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Murid Bimbingan</p>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.13m4.786-3.07a9.348 9.348 0 00-2.813-1.077M14.214 16.06c-.822-.656-1.854-1.06-2.964-1.06-1.11 0-2.142.404-2.964 1.06m8.892 0c.501.91.786 1.957.786 3.07v.003m-11.784 0a4.125 4.125 0 01-7.533-2.493 9.337 9.337 0 014.121-.952 9.38 9.38 0 012.625.372m0 3.07c0-1.113.285-2.16.786-3.07m-5.412 3.07v.109A2.25 2.25 0 004.5 21.5h1.5a2.25 2.25 0 002.25-2.263V19.13m4.786-3.07a9.348 9.348 0 012.813-1.077M8.906 16.06a9.38 9.38 0 00-2.813-1.077m0 0a9.338 9.338 0 015.626 0M8.906 16.06v-.003c0-1.113.285-2.16.786-3.07M12 12a3 3 0 100-6 3 3 0 000 6z" /></svg>
                        </div>
                    </div>
                    <div class="mt-2.5">
                        <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'total_students', 0) }}</p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">Santri terdaftar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-sm border border-zinc-200/80 dark:border-white/[0.08] rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Setoran Hari Ini</p>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                    </div>
                    <div class="mt-2.5">
                        <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ data_get($stats, 'hafalan_today', 0) }}</p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400 font-semibold">Murajaah: {{ data_get($stats, 'murajaah_today', 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-sm border border-zinc-200/80 dark:border-white/[0.08] rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Aktif</p>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="mt-2.5">
                        <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'active_targets', 0) }}</p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 font-semibold">Terlambat: {{ data_get($stats, 'overdue_targets', 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-sm border border-zinc-200/80 dark:border-white/[0.08] rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Butuh Perhatian</p>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                    </div>
                    <div class="mt-2.5">
                        <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                            {{ data_get($stats, 'hafalan_need_attention', 0) + data_get($stats, 'murajaah_need_attention', 0) }}
                        </p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">Setoran &amp; Murajaah</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ QUICK ACTION RIBBON (TOUCH-OPTIMIZED) ═══════════════ --}}
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                <a href="{{ url('/hafalan-records/create') }}" class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm rounded-2xl p-2.5 sm:p-4 hover:border-emerald-500/40 hover:shadow-md transition-all duration-150 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2 sm:gap-3.5 group">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Input Setoran</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Setoran hafalan baru.</p>
                    </div>
                </a>

                <a href="{{ route('murajaah-records.fast-input') }}" class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm rounded-2xl p-2.5 sm:p-4 hover:border-amber-500/40 hover:shadow-md transition-all duration-150 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2 sm:gap-3.5 group">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.656 48.656 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7C4.547 9.547 4.5 10.768 4.5 12s.047 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.092-1.209.138-2.43.138-3.662zM9 10.5h6M9 13.5h6" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Input Murajaah</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Pengulangan hafalan.</p>
                    </div>
                </a>

                <a href="{{ url('/hafalan-targets/create') }}" class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm rounded-2xl p-2.5 sm:p-4 hover:border-teal-500/40 hover:shadow-md transition-all duration-150 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2 sm:gap-3.5 group">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Buat Target</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Sasaran hafalan murid.</p>
                    </div>
                </a>
            </div>

            {{-- ═══════════════ PROGRESS MURID BIMBINGAN (RESPONSIVE TABLE / CARDS) ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 shadow-sm border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl overflow-hidden">
                <div class="px-4 py-3.5 sm:px-6 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white">Progres Murid Bimbingan</h3>
                    </div>
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500">{{ $studentsProgress->count() }} Santri</span>
                </div>

                {{-- Mobile Card List View (< sm) --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($studentsProgress as $item)
                        @php
                            $student = data_get($item, 'student');
                            $percentage = (float) data_get($item, 'progress_percentage', data_get($item, 'progress_percent', 0));
                            $activeTargetCount = (int) data_get($item, 'active_targets', data_get($item, 'active_target_count', 0));
                            $overdueTargetCount = (int) data_get($item, 'overdue_targets', data_get($item, 'overdue_target_count', 0));
                        @endphp
                        <div class="p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($student?->name ?? data_get($item, 'student_name', 'A'), 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs text-zinc-900 dark:text-white truncate">{{ $student?->name ?? data_get($item, 'student_name', '-') }}</p>
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $student?->classRoom?->name ?? data_get($item, 'class_room_name', '-') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ $activeTargetCount }} Target
                                    </span>
                                    @if ($overdueTargetCount > 0)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            {{ $overdueTargetCount }} Terlambat
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[10px] text-zinc-500 dark:text-zinc-400">
                                    <span>Ketercapaian Hafalan</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $percentage }}%</span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-1.5 rounded-full transition-all duration-300" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-zinc-500">Belum ada murid bimbingan.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View (>= sm) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 text-xs uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Murid</th>
                                <th class="px-5 py-3.5 text-left">Kelas</th>
                                <th class="px-5 py-3.5 text-left">Progress</th>
                                <th class="px-5 py-3.5 text-center">Target Aktif</th>
                                <th class="px-5 py-3.5 text-center">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($studentsProgress as $item)
                                @php
                                    $student = data_get($item, 'student');
                                    $percentage = (float) data_get($item, 'progress_percentage', data_get($item, 'progress_percent', 0));
                                    $activeTargetCount = (int) data_get($item, 'active_targets', data_get($item, 'active_target_count', 0));
                                    $overdueTargetCount = (int) data_get($item, 'overdue_targets', data_get($item, 'overdue_target_count', 0));
                                @endphp
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-zinc-900 dark:text-white">{{ $student?->name ?? data_get($item, 'student_name', '-') }}</td>
                                    <td class="px-5 py-3.5 text-zinc-600 dark:text-zinc-400 font-medium">{{ $student?->classRoom?->name ?? data_get($item, 'class_room_name', '-') }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-36 bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ $percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                            {{ $activeTargetCount }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $overdueTargetCount > 0 ? 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300' : 'text-zinc-400' }}">
                                            {{ $overdueTargetCount }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6 text-center text-zinc-500 text-sm">Belum ada murid bimbingan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ═══════════════ TARGET, SETORAN & MURAJAAH (COMPACT TILES) ═══════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                {{-- Target Terdekat --}}
                <div class="bg-white dark:bg-zinc-900 shadow-sm border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl overflow-hidden">
                    <div class="px-4 py-3.5 sm:px-5 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <h3 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>🎯</span> Target Terdekat
                        </h3>
                        <a href="{{ url('/hafalan-targets') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Lihat Semua</a>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($latestTargets as $target)
                            <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $target->student?->name ?? '-' }}</p>
                                        @if ($target->ummi_jilid)
                                            <p class="text-xs font-semibold text-teal-700 dark:text-teal-400 mt-0.5">📗 {{ $target->ummi_jilid }} (Halaman: {{ $target->halaman_buku ?? '-' }})</p>
                                        @else
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] sm:text-xs font-semibold text-zinc-500">{{ $target->target_date?->format('d M') }}</p>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold {{ $target->is_overdue ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' }}">
                                            {{ $target->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-zinc-500">Belum ada target.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Setoran Terbaru --}}
                <div class="bg-white dark:bg-zinc-900 shadow-sm border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl overflow-hidden">
                    <div class="px-4 py-3.5 sm:px-5 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <h3 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>📖</span> Setoran Terbaru
                        </h3>
                        <a href="{{ url('/hafalan-records') }}" class="text-xs text-orange-600 dark:text-orange-400 font-bold hover:underline">Semua</a>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_range }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                            {{ $record->status_label }}
                                        </span>
                                        <p class="text-[10px] text-zinc-400 mt-0.5">{{ $record->submitted_at?->format('d M') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-zinc-500">Belum ada setoran.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Murajaah Terbaru --}}
                <div class="bg-white dark:bg-zinc-900 shadow-sm border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl overflow-hidden">
                    <div class="px-4 py-3.5 sm:px-5 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <h3 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>🔄</span> Murajaah Terbaru
                        </h3>
                        <a href="{{ url('/murajaah-records') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline">Semua</a>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($latestMurajaahRecords as $record)
                            <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_range }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                                            {{ $record->status_label }}
                                        </span>
                                        <p class="text-[10px] text-zinc-400 mt-0.5">{{ $record->reviewed_at?->format('d M') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-zinc-500">Belum ada murajaah.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>