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
        $avgClassProgress = $studentsProgress->isNotEmpty() 
            ? round($studentsProgress->avg(fn($s) => (float) data_get($s, 'progress_percentage', data_get($s, 'progress_percent', 0))), 1) 
            : 0;
    @endphp

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            @if (! data_get($stats, 'teacher'))
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 rounded-2xl p-4 text-xs sm:text-sm font-semibold">
                    Akun guru ini belum memiliki profil guru. Hubungi admin.
                </div>
            @endif

            {{-- 🌟 TEACHER HERO BENTO WITH CLASS CIRCULAR PROGRESS RING 🌟 --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
                
                {{-- Left 4-col: Class Average Completion Ring --}}
                <div class="lg:col-span-4 glass-liquid-card rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col items-center justify-between text-center relative overflow-hidden border border-teal-500/20 shadow-md">
                    <div class="w-full flex items-center justify-between mb-2">
                        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400 bg-teal-500/10 px-2.5 py-1 rounded-xl">
                            <span>📊</span> Rata-Rata Bimbingan
                        </span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
                            {{ $studentsProgress->count() }} Murid
                        </span>
                    </div>

                    <div class="relative w-36 h-36 sm:w-40 sm:h-40 my-3 flex items-center justify-center">
                        @php
                            $dashTeacher = 2 * 3.14159 * 44;
                            $offsetTeacher = $dashTeacher - ($avgClassProgress / 100) * $dashTeacher;
                        @endphp
                        <svg class="w-full h-full transform -rotate-90 glow-teal-ring" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="8" class="text-zinc-200 dark:text-zinc-800" fill="none" />
                            <circle cx="50" cy="50" r="44" stroke="url(#teacherTealGrad)" stroke-width="8" stroke-dasharray="{{ $dashTeacher }}" stroke-dashoffset="{{ $offsetTeacher }}" stroke-linecap="round" fill="none" />
                            <defs>
                                <linearGradient id="teacherTealGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#0d9488" />
                                    <stop offset="100%" stop-color="#10b981" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute flex flex-col items-center justify-center text-center">
                            <span class="text-3xl sm:text-4xl font-black text-zinc-900 dark:text-white tracking-tight">{{ round($avgClassProgress) }}%</span>
                            <span class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase">Tuntas Target</span>
                        </div>
                    </div>

                    <div class="w-full pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400">Total Bimbingan</span>
                        <strong class="text-zinc-900 dark:text-white font-black">{{ data_get($stats, 'total_students', 0) }} Santri Aktif</strong>
                    </div>
                </div>

                {{-- Right 8-col: 4 Stat Cards Grid --}}
                <div class="lg:col-span-8 grid grid-cols-2 gap-3 sm:gap-4">
                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-zinc-200/70 dark:border-white/10">
                        <div class="flex items-center justify-between gap-1">
                            <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Setoran Hari Ini</p>
                            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-400/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 shadow-xs">
                                <span>📖</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ data_get($stats, 'hafalan_today', 0) }}</p>
                            <div class="flex items-center gap-1.5 mt-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                                <p class="text-[10px] sm:text-xs text-emerald-700 dark:text-emerald-400 font-semibold">Setoran baru masuk</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-zinc-200/70 dark:border-white/10">
                        <div class="flex items-center justify-between gap-1">
                            <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Murajaah Hari Ini</p>
                            <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-400/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-xs">
                                <span>🔄</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ data_get($stats, 'murajaah_today', 0) }}</p>
                            <div class="flex items-center gap-1.5 mt-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                                <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400 font-semibold">Pengulangan hafalan</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-zinc-200/70 dark:border-white/10">
                        <div class="flex items-center justify-between gap-1">
                            <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Aktif</p>
                            <div class="w-8 h-8 rounded-xl bg-teal-500/10 dark:bg-teal-400/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 shadow-xs">
                                <span>🎯</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'active_targets', 0) }}</p>
                            <div class="flex items-center gap-1.5 mt-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                                <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400 font-semibold">Sasaran pekan ini</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-zinc-200/70 dark:border-white/10">
                        <div class="flex items-center justify-between gap-1">
                            <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Butuh Perhatian</p>
                            <div class="w-8 h-8 rounded-xl bg-rose-500/10 dark:bg-rose-400/15 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 shadow-xs">
                                <span>⚠️</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                                {{ data_get($stats, 'hafalan_need_attention', 0) + data_get($stats, 'murajaah_need_attention', 0) }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                                <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 font-bold">Terlambat: {{ data_get($stats, 'overdue_targets', 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ QUICK ACTION RIBBON (FROSTED GLASS BUTTONS) ═══════════════ --}}
            <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
                <a href="{{ url('/hafalan-records/create') }}" class="glass-liquid-card rounded-2xl p-3 sm:p-4.5 hover:border-emerald-500/40 hover:shadow-md transition-all duration-200 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2.5 sm:gap-3.5 group border border-emerald-500/20">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Input Setoran</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Setoran hafalan baru.</p>
                    </div>
                </a>

                <a href="{{ route('murajaah-records.fast-input') }}" class="glass-liquid-card rounded-2xl p-3 sm:p-4.5 hover:border-amber-500/40 hover:shadow-md transition-all duration-200 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2.5 sm:gap-3.5 group border border-amber-500/20">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.656 48.656 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7C4.547 9.547 4.5 10.768 4.5 12s.047 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.092-1.209.138-2.43.138-3.662zM9 10.5h6M9 13.5h6" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Input Murajaah</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Pengulangan hafalan.</p>
                    </div>
                </a>

                <a href="{{ url('/hafalan-targets/create') }}" class="glass-liquid-card rounded-2xl p-3 sm:p-4.5 hover:border-teal-500/40 hover:shadow-md transition-all duration-200 flex flex-col sm:flex-row items-center text-center sm:text-left gap-2.5 sm:gap-3.5 group border border-teal-500/20">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 group-hover:bg-teal-500 group-hover:text-white transition shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Buat Target</h4>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 hidden sm:block">Sasaran hafalan murid.</p>
                    </div>
                </a>
            </div>

            {{-- ═══════════════ PROGRESS MURID BIMBINGAN (FROSTED GLASS CONTAINER) ═══════════════ --}}
            <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                <div class="px-5 py-4 sm:px-6 sm:py-4.5 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-teal-500 animate-pulse"></div>
                        <h3 class="font-bold text-base text-zinc-900 dark:text-white">Progres Murid Bimbingan</h3>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">{{ $studentsProgress->count() }} Santri</span>
                </div>

                {{-- Mobile Card List View (< sm) --}}
                <div class="block sm:hidden divide-y divide-zinc-200/50 dark:divide-white/5 p-3 space-y-2.5">
                    @forelse ($studentsProgress as $item)
                        @php
                            $student = data_get($item, 'student');
                            $percentage = (float) data_get($item, 'progress_percentage', data_get($item, 'progress_percent', 0));
                            $activeTargetCount = (int) data_get($item, 'active_targets', data_get($item, 'active_target_count', 0));
                            $overdueTargetCount = (int) data_get($item, 'overdue_targets', data_get($item, 'overdue_target_count', 0));
                        @endphp
                        <div class="p-3.5 rounded-xl glass-liquid-inner space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-teal-500/15 text-teal-700 dark:text-teal-300 font-bold text-xs flex items-center justify-center shrink-0 border border-teal-500/20">
                                        {{ substr($student?->name ?? data_get($item, 'student_name', 'A'), 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs text-zinc-900 dark:text-white truncate">{{ $student?->name ?? data_get($item, 'student_name', '-') }}</p>
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $student?->classRoom?->name ?? data_get($item, 'class_room_name', '-') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                        {{ $activeTargetCount }} Target
                                    </span>
                                    @if ($overdueTargetCount > 0)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20">
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
                                <div class="w-full bg-zinc-200/80 dark:bg-zinc-800 rounded-full h-2 overflow-hidden shadow-inner">
                                    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($percentage, 100) }}%"></div>
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
                        <thead class="bg-zinc-100/50 dark:bg-white/[0.02] text-zinc-600 dark:text-zinc-300 text-xs uppercase tracking-wider font-semibold border-b border-zinc-200/70 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Murid</th>
                                <th class="px-6 py-3.5 text-left">Kelas</th>
                                <th class="px-6 py-3.5 text-left">Progress</th>
                                <th class="px-6 py-3.5 text-center">Target Aktif</th>
                                <th class="px-6 py-3.5 text-center">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($studentsProgress as $item)
                                @php
                                    $student = data_get($item, 'student');
                                    $percentage = (float) data_get($item, 'progress_percentage', data_get($item, 'progress_percent', 0));
                                    $activeTargetCount = (int) data_get($item, 'active_targets', data_get($item, 'active_target_count', 0));
                                    $overdueTargetCount = (int) data_get($item, 'overdue_targets', data_get($item, 'overdue_target_count', 0));
                                @endphp
                                <tr class="hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <td class="px-6 py-3.5 font-bold text-zinc-900 dark:text-white">{{ $student?->name ?? data_get($item, 'student_name', '-') }}</td>
                                    <td class="px-6 py-3.5 text-zinc-600 dark:text-zinc-400 font-medium">{{ $student?->classRoom?->name ?? data_get($item, 'class_room_name', '-') }}</td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-36 bg-zinc-200/80 dark:bg-zinc-800 rounded-full h-2 overflow-hidden shadow-inner">
                                                <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 min-w-[35px]">{{ $percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                            {{ $activeTargetCount }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $overdueTargetCount > 0 ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20' : 'text-zinc-400' }}">
                                            {{ $overdueTargetCount }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-500 text-sm">Belum ada murid bimbingan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ═══════════════ TARGET, SETORAN & MURAJAAH (FROSTED GLASS TILES) ═══════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                {{-- Target Terdekat --}}
                <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                        <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>🎯</span> Target Terdekat
                        </h3>
                        <a href="{{ url('/hafalan-targets') }}" class="text-xs text-teal-600 dark:text-teal-400 font-bold hover:underline">Lihat Semua &rarr;</a>
                    </div>
                    <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                        @forelse ($latestTargets as $target)
                            <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $target->student?->name ?? '-' }}</p>
                                        @if ($target->ummi_jilid)
                                            <p class="text-xs font-semibold text-teal-700 dark:text-teal-400 mt-0.5">📗 {{ $target->ummi_jilid }} (Hal: {{ $target->halaman_buku ?? '-' }})</p>
                                        @else
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $target->target_date?->format('d M') }}</p>
                                        <span class="inline-block mt-0.5 px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold {{ $target->is_overdue ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20' : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20' }}">
                                            {{ $target->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-zinc-500">Belum ada target.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Setoran Terbaru --}}
                <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                        <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>📖</span> Setoran Terbaru
                        </h3>
                        <a href="{{ url('/hafalan-records') }}" class="text-xs text-orange-600 dark:text-orange-400 font-bold hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_range }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                            {{ $record->status_label }}
                                        </span>
                                        <p class="text-[10px] text-zinc-400 mt-1">{{ $record->submitted_at?->format('d M') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-zinc-500">Belum ada setoran.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Murajaah Terbaru --}}
                <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                        <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <span>🔄</span> Murajaah Terbaru
                        </h3>
                        <a href="{{ url('/murajaah-records') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                        @forelse ($latestMurajaahRecords as $record)
                            <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_range }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                            {{ $record->status_label }}
                                        </span>
                                        <p class="text-[10px] text-zinc-400 mt-1">{{ $record->reviewed_at?->format('d M') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-zinc-500">Belum ada murajaah.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>