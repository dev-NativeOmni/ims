<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-950 dark:text-white tracking-tight">
                    Dashboard Guru & Musyrif
                </h2>
                <p class="text-xs sm:text-sm text-slate-700 dark:text-zinc-300 font-medium mt-0.5">
                    Monitoring santri bimbingan, setoran hafalan, muraja'ah, dan target semester.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3.5 py-1 rounded-full text-xs font-extrabold bg-white/80 dark:bg-white/10 text-teal-950 dark:text-teal-200 border border-white/80 dark:border-white/20 shadow-sm backdrop-blur-md">
                    {{ now()->translatedFormat('l, d F Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    @php
        $studentsProgress = collect(data_get($stats, 'students_progress', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="space-y-6">

        @if (! data_get($stats, 'teacher'))
            <div class="p-4 rounded-2xl bg-rose-500/20 border border-rose-500/30 text-rose-950 dark:text-rose-200 text-sm font-extrabold backdrop-blur-md">
                ⚠️ Akun ini belum terhubung dengan profil guru. Hubungi admin untuk mengaitkan profil.
            </div>
        @endif

        <!-- Stat Bento Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Murid Bimbingan -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0 1 12.75 21.5h-1.5a2.25 2.25 0 0 1-2.25-2.263V19.13" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-500/20 text-teal-950 dark:text-teal-200 border border-teal-500/30">
                        Bimbingan
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Murid Bimbingan</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'total_students', 0) }}
                    </p>
                </div>
            </div>

            <!-- Stat 2: Setoran Hari Ini -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200 border border-emerald-500/30">
                        Muraja'ah {{ data_get($stats, 'murajaah_today', 0) }}
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Setoran Hari Ini</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'hafalan_today', 0) }}
                    </p>
                </div>
            </div>

            <!-- Stat 3: Target Aktif -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-800 dark:text-amber-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    @if(data_get($stats, 'overdue_targets', 0) > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-500/20 text-rose-950 dark:text-rose-200 border border-rose-500/30">
                            Terlambat {{ data_get($stats, 'overdue_targets', 0) }}
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200 border border-emerald-500/30">
                            Aktif
                        </span>
                    @endif
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Target Aktif</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'active_targets', 0) }}
                    </p>
                </div>
            </div>

            <!-- Stat 4: Butuh Perhatian -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-800 dark:text-rose-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-500/20 text-rose-950 dark:text-rose-200 border border-rose-500/30">
                        Evaluasi
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Butuh Perhatian</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'hafalan_need_attention', 0) + data_get($stats, 'murajaah_need_attention', 0) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Action Tiles -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ url('/hafalan-records/create') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-emerald-800 dark:group-hover:text-emerald-300 transition-colors">Input Setoran</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Catat setoran hafalan baru murid.</p>
                </div>
            </a>

            <a href="{{ url('/murajaah-records/create') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-800 dark:text-amber-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.656 48.656 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7C4.547 9.547 4.5 10.768 4.5 12s.047 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.092-1.209.138-2.43.138-3.662Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-amber-800 dark:group-hover:text-amber-300 transition-colors">Input Muraja'ah</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Catat pengulangan hafalan.</p>
                </div>
            </a>

            <a href="{{ url('/hafalan-targets/create') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-teal-800 dark:group-hover:text-teal-300 transition-colors">Buat Target</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Tetapkan target hafalan murid.</p>
                </div>
            </a>
        </div>

        <!-- Table Card: Progres Murid Bimbingan -->
        <div class="glass-card rounded-3xl overflow-hidden p-5 sm:p-6">
            <div class="flex items-center justify-between pb-4 border-b border-white/40 dark:border-white/10">
                <div>
                    <h3 class="font-extrabold text-base text-slate-950 dark:text-white tracking-tight">Progress Murid Bimbingan</h3>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Capaian santri dalam kelompok bimbingan Anda.</p>
                </div>
            </div>

            <div class="overflow-x-auto mt-3">
                <table class="min-w-full text-sm divide-y divide-white/30 dark:divide-white/5">
                    <thead>
                        <tr class="text-left text-xs font-extrabold text-slate-800 dark:text-zinc-200 uppercase tracking-wider">
                            <th class="py-3 px-3">Murid</th>
                            <th class="py-3 px-3">Kelas</th>
                            <th class="py-3 px-3">Progress</th>
                            <th class="py-3 px-3">Target Aktif</th>
                            <th class="py-3 px-3">Terlambat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/20 dark:divide-white/5">
                        @forelse ($studentsProgress as $item)
                            @php
                                $student = data_get($item, 'student');
                                $percentage = (float) data_get($item, 'progress_percentage', data_get($item, 'progress_percent', 0));
                                $activeTargetCount = (int) data_get($item, 'active_targets', data_get($item, 'active_target_count', 0));
                                $overdueTargetCount = (int) data_get($item, 'overdue_targets', data_get($item, 'overdue_target_count', 0));
                            @endphp
                            <tr class="hover:bg-white/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-3 font-extrabold text-slate-950 dark:text-white">{{ $student?->name ?? data_get($item, 'student_name', '-') }}</td>
                                <td class="py-3.5 px-3 text-slate-700 dark:text-zinc-300 font-semibold">{{ $student?->classRoom?->name ?? data_get($item, 'class_room_name', '-') }}</td>
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-36 sm:w-48 bg-white/70 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden border border-white/60 dark:border-white/10">
                                            <div class="bg-gradient-to-r from-teal-600 to-emerald-600 h-2.5 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-extrabold text-teal-950 dark:text-teal-300">{{ $percentage }}%</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-extrabold text-slate-900 dark:text-zinc-100">{{ $activeTargetCount }}</td>
                                <td class="py-3.5 px-3 font-extrabold {{ $overdueTargetCount > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-zinc-400' }}">{{ $overdueTargetCount }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-slate-600 dark:text-zinc-400 font-medium">Belum ada murid bimbingan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2 Column Section: Target & Setoran Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Target Terdekat -->
            <div class="glass-card rounded-3xl p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/40 dark:border-white/10">
                    <h3 class="font-extrabold text-base text-slate-950 dark:text-white tracking-tight">Target Terdekat</h3>
                    <a href="{{ url('/hafalan-targets') }}" class="text-xs font-extrabold text-teal-950 dark:text-teal-300 hover:underline">Kelola &rarr;</a>
                </div>
                <div class="divide-y divide-white/20 dark:divide-white/5 space-y-2">
                    @forelse ($latestTargets as $target)
                        <div class="pt-3 first:pt-0 flex justify-between gap-4">
                            <div>
                                <p class="font-extrabold text-sm text-slate-950 dark:text-white">{{ $target->student?->name ?? '-' }}</p>
                                @if ($target->ummi_jilid)
                                    <p class="text-xs font-extrabold text-teal-950 dark:text-teal-300 mt-0.5">📗 {{ $target->ummi_jilid }} (Peraga: {{ $target->halaman_peraga ?? '-' }}, Buku: {{ $target->halaman_buku ?? '-' }})</p>
                                @else
                                    <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300 mt-0.5">{{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-slate-950 dark:text-white">{{ $target->target_date?->format('d M Y') }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded-lg text-[10px] font-extrabold {{ $target->is_overdue ? 'bg-rose-500/20 text-rose-950 dark:text-rose-200' : 'bg-emerald-500/20 text-emerald-950 dark:text-emerald-200' }}">
                                    {{ $target->status_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-600 dark:text-zinc-400 font-medium">Belum ada target aktif.</div>
                    @endforelse
                </div>
            </div>

            <!-- Setoran Terbaru -->
            <div class="glass-card rounded-3xl p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/40 dark:border-white/10">
                    <h3 class="font-extrabold text-base text-slate-950 dark:text-white tracking-tight">Setoran Hafalan Terbaru</h3>
                    <span class="text-xs text-slate-600 dark:text-zinc-400 font-semibold">Riwayat</span>
                </div>
                <div class="divide-y divide-white/20 dark:divide-white/5 space-y-2">
                    @forelse ($latestHafalanRecords as $record)
                        <div class="pt-3 first:pt-0 flex items-start justify-between gap-4">
                            <div>
                                <p class="font-extrabold text-sm text-slate-950 dark:text-white">{{ $record->student?->name ?? '-' }}</p>
                                <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300 mt-0.5">{{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-slate-800 dark:text-zinc-200">{{ $record->submitted_at?->format('d M Y') }}</p>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200">
                                    {{ $record->status_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-600 dark:text-zinc-400 font-medium">Belum ada setoran.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>