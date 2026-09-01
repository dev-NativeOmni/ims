<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-950 dark:text-white tracking-tight">
                    {{ $title ?? 'Executive Overview' }}
                </h2>
                <p class="text-xs sm:text-sm text-slate-700 dark:text-zinc-300 font-medium mt-0.5">
                    {{ $subtitle ?? 'Monitoring operasional & progres pembelajaran Al-Qur\'an.' }}
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

        <!-- Top Bento Stat Cards (High-Contrast Frosted Glass) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <!-- Stat 1: Total Murid -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0 1 12.75 21.5h-1.5a2.25 2.25 0 0 1-2.25-2.263V19.13" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200 border border-emerald-500/30">
                        Aktif {{ data_get($stats, 'active_students', 0) }}
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Total Santri</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'total_students', 0) }}
                    </p>
                </div>
            </div>

            <!-- Stat 2: Guru & Wali -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-800 dark:text-indigo-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-500/20 text-indigo-950 dark:text-indigo-200 border border-indigo-500/30">
                        Wali {{ data_get($stats, 'total_parents', 0) }}
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Musyrif / Guru</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'total_teachers', 0) }}
                    </p>
                </div>
            </div>

            <!-- Stat 3: Setoran Hari Ini -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-500/20 text-teal-950 dark:text-teal-200 border border-teal-500/30">
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

            <!-- Stat 4: Target Aktif -->
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
                            Tepat Waktu
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

            <!-- Stat 5: Adab Hari Ini -->
            <div class="glass-card glass-card-hover rounded-3xl p-5 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-center justify-center border border-white/60 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200 border border-emerald-500/30">
                        Monitoring
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-zinc-300 uppercase tracking-wider block">Adab Terisi</span>
                    <p class="text-3xl font-black text-slate-950 dark:text-white tracking-tight mt-0.5">
                        {{ data_get($stats, 'adab_filled_today', 0) }}<span class="text-sm font-bold text-slate-600 dark:text-zinc-400">/{{ data_get($stats, 'adab_total_students', 0) }}</span>
                    </p>
                </div>
            </div>

        </div>

        <!-- Quick Access Bento Tiles -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ url('/students') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0 1 12.75 21.5h-1.5a2.25 2.25 0 0 1-2.25-2.263V19.13" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-emerald-800 dark:group-hover:text-emerald-300 transition-colors">Kelola Murid</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Data murid, kelas, guru.</p>
                </div>
            </a>

            <a href="{{ url('/hafalan-targets') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-800 dark:text-amber-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-amber-800 dark:group-hover:text-amber-300 transition-colors">Target Hafalan</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Target aktif & selesai.</p>
                </div>
            </a>

            <a href="{{ route('adab.index') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-teal-800 dark:group-hover:text-teal-300 transition-colors">Monitoring Adab</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Penilaian karakter santri.</p>
                </div>
            </a>

            <a href="{{ route('adab-materials.index') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 text-cyan-800 dark:text-cyan-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-cyan-800 dark:group-hover:text-cyan-300 transition-colors">Materi Adab</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Panduan halaqoh adab.</p>
                </div>
            </a>

            <a href="{{ url('/reports') }}" class="glass-card glass-card-hover rounded-3xl p-5 flex items-center gap-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-800 dark:text-rose-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60 dark:border-white/10 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-950 dark:text-white group-hover:text-rose-800 dark:group-hover:text-rose-300 transition-colors">Rapor Digital</h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Ekspor rapor & berkas.</p>
                </div>
            </a>
        </div>

        <!-- Table Card: Progres Murid Aktif -->
        <div class="glass-card rounded-3xl overflow-hidden p-5 sm:p-6">
            <div class="flex items-center justify-between pb-4 border-b border-white/40 dark:border-white/10">
                <div>
                    <h3 class="font-extrabold text-base text-slate-950 dark:text-white tracking-tight">Progress Murid Aktif</h3>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium mt-0.5">Diurutkan dari capaian progres tertinggi.</p>
                </div>
                <a href="{{ url('/students') }}" class="px-3.5 py-1.5 rounded-xl bg-white/80 dark:bg-white/10 text-teal-950 dark:text-teal-200 hover:bg-white dark:hover:bg-white/20 text-xs font-extrabold border border-white/80 dark:border-white/10 transition shadow-sm">
                    Lihat Semua &rarr;
                </a>
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
                                $student = $item['student'];
                                $percentage = $item['progress_percentage'] ?? 0;
                            @endphp
                            <tr class="hover:bg-white/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-3 font-extrabold text-slate-950 dark:text-white">{{ $student->name }}</td>
                                <td class="py-3.5 px-3 text-slate-700 dark:text-zinc-300 font-semibold">
                                    {{ $student->classRoom?->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-36 sm:w-48 bg-white/70 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden border border-white/60 dark:border-white/10">
                                            <div class="bg-gradient-to-r from-teal-600 to-emerald-600 h-2.5 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-extrabold text-teal-950 dark:text-teal-300">{{ $percentage }}%</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-extrabold text-slate-900 dark:text-zinc-100">{{ $item['active_target_count'] ?? 0 }}</td>
                                <td class="py-3.5 px-3 font-extrabold {{ ($item['overdue_target_count'] ?? 0) > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-zinc-400' }}">
                                    {{ $item['overdue_target_count'] ?? 0 }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-slate-600 dark:text-zinc-400 font-medium">Belum ada data progress.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2 Column Section: Target & Setoran Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card 1: Target Terdekat -->
            <div class="glass-card rounded-3xl p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/40 dark:border-white/10">
                    <h3 class="font-extrabold text-base text-slate-950 dark:text-white tracking-tight">Target Terdekat</h3>
                    <span class="text-xs text-slate-600 dark:text-zinc-400 font-semibold">Batas Waktu</span>
                </div>
                <div class="divide-y divide-white/20 dark:divide-white/5 space-y-2">
                    @forelse ($latestTargets as $target)
                        <div class="pt-3 first:pt-0 flex items-start justify-between gap-4">
                            <div>
                                <p class="font-extrabold text-sm text-slate-950 dark:text-white">{{ $target->student?->name ?? '-' }}</p>
                                <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300 mt-0.5">
                                    {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}
                                </p>
                                <p class="text-[11px] text-slate-600 dark:text-zinc-400 mt-0.5 font-medium">
                                    Guru: {{ $target->teacher?->user?->name ?? '-' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-slate-950 dark:text-white">
                                    {{ $target->target_date?->format('d M Y') }}
                                </p>
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

            <!-- Card 2: Setoran Hafalan Terbaru -->
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
                                <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300 mt-0.5">
                                    {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-slate-800 dark:text-zinc-200">
                                    {{ $record->submitted_at?->format('d M Y') }}
                                </p>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-500/20 text-emerald-950 dark:text-emerald-200">
                                    {{ $record->status_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-600 dark:text-zinc-400 font-medium">Belum ada setoran tercatat.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>