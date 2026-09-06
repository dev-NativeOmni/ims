<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-base sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Dashboard Orang Tua
            </h2>
            <p class="text-[11px] sm:text-sm text-zinc-500 dark:text-zinc-400">
                Pusat monitoring terpadu perkembangan Tahfizh, Adab, dan Kedisiplinan Ananda.
            </p>
        </div>
    </x-slot>

    @php
        $parent = data_get($stats, 'parent');
        $children = collect(data_get($stats, 'children', []));
        $childrenProgress = collect(data_get($stats, 'children_progress', []));
        $childrenMotivation = collect(data_get($stats, 'children_motivation', []));
    @endphp

    <div class="py-3 sm:py-6">
        <div class="max-w-7xl mx-auto px-2.5 sm:px-6 lg:px-8 space-y-3.5 sm:space-y-6">

            @if (! $parent)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 sm:p-5 text-xs sm:text-sm text-amber-800 dark:text-amber-300 font-semibold">
                    Profil orang tua belum terhubung dengan akun ini. Silakan hubungi admin sekolah.
                </div>
            @elseif ($childrenProgress->isEmpty())
                <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-5 sm:p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400 font-medium text-xs sm:text-sm">
                        Belum ada data murid yang ditautkan ke akun Anda.
                    </p>
                </div>
            @else
                {{-- 🌟 SAMBUTAN HANGAT & RINGKASAN PORTAL ORANG TUA 🌟 --}}
                <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-3.5 sm:p-6 shadow-xs sm:shadow-sm relative overflow-hidden">
                    <div class="relative z-10 flex items-center justify-between gap-3">
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-teal-500/15 border border-teal-500/20 text-teal-700 dark:text-teal-300 text-[10px] sm:text-xs font-bold uppercase tracking-wider">
                                    <x-heroicon-o-sparkles class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-teal-600 dark:text-teal-400" /> Portal Khusus Wali Murid
                                </span>
                            </div>
                            <h3 class="text-sm sm:text-lg lg:text-xl font-black text-zinc-900 dark:text-white truncate">
                                Assalamu'alaikum, Ayah / Bunda {{ $parent->user?->name ?? '' }}!
                            </h3>
                            <p class="text-[11px] sm:text-xs text-zinc-600 dark:text-zinc-400 font-medium line-clamp-1 sm:line-clamp-none">
                                Ruang sinergi pemantauan ananda dalam hafalan Al-Qur'an, karakter islami, dan kedisiplinan.
                            </p>
                        </div>

                        <div class="shrink-0">
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner px-2.5 py-1.5 sm:px-4 sm:py-2.5 text-center">
                                <p class="text-[8px] sm:text-[10px] uppercase font-bold text-teal-700 dark:text-teal-300 tracking-wider">Ananda</p>
                                <p class="text-xs sm:text-lg font-black text-zinc-900 dark:text-white">{{ $childrenProgress->count() }} Murid</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ MULTI-CHILD MONITORING HUB ═══════════════ --}}
                <div x-data="{ activeChild: 0 }" class="space-y-3.5 sm:space-y-6">

                    @if ($childrenProgress->count() > 1)
                        {{-- Tab Selector for multiple children (Touch-scrollable) --}}
                        <div class="glass-liquid-card p-1.5 sm:p-2 rounded-xl sm:rounded-2xl flex items-center gap-1.5 overflow-x-auto scrollbar-none">
                            <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-2 flex items-center gap-1 shrink-0">
                                <x-heroicon-o-user-group class="w-3.5 h-3.5" /> Ananda:
                            </span>
                            @foreach ($childrenProgress as $idx => $row)
                                <button @click="activeChild = {{ $idx }}"
                                        :class="activeChild === {{ $idx }} ? 'bg-teal-600 text-white shadow-xs font-black' : 'glass-liquid-inner text-zinc-700 dark:text-zinc-300 hover:bg-white/60 dark:hover:bg-white/10 font-semibold'"
                                        class="flex items-center gap-1 rounded-lg sm:rounded-xl px-2.5 py-1.5 sm:px-3.5 sm:py-2 text-xs transition-all shrink-0 cursor-pointer">
                                    <x-heroicon-o-user class="w-3 h-3" />
                                    <span class="truncate max-w-[120px] sm:max-w-none">{{ data_get($row, 'student_name', 'Ananda '.($idx+1)) }}</span>
                                    <span class="text-[10px] opacity-80">({{ data_get($row, 'class_room_name', '-') }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Individual Full Profile Cards per Child --}}
                    @foreach ($childrenProgress as $idx => $row)
                        @php
                            $student = data_get($row, 'student');
                            $className = data_get($row, 'class_room_name', $student?->classRoom?->name ?? '');
                            $classLevel = $student?->classRoom?->level ?? '';
                            $isGrade10Class = (bool) (
                                (preg_match('/\bX\b/i', $className) && !preg_match('/\b(XI|XII)\b/i', $className))
                                || preg_match('/\b10\b/i', $className)
                                || preg_match('/^X[-_\s]?E/i', $className)
                                || preg_match('/kelas\s*(X|10)/i', $className)
                                || (preg_match('/\bX\b/i', $classLevel) && !preg_match('/\b(XI|XII)\b/i', $classLevel))
                                || preg_match('/\b10\b/i', $classLevel)
                            ) && !preg_match('/\b(XI|XII|11|12)\b/i', $className);

                            $isUmmi = data_get($row, 'is_ummi_program', false) || $isGrade10Class;
                            $statusColor = data_get($row, 'status_color', 'emerald');
                            $statusLabel = data_get($row, 'status_label', 'On-Track / Tuntas');
                            $statusIcon = data_get($row, 'status_icon', '🟢');

                            $adabData = data_get($row, 'adab', []);
                            $tanseData = data_get($row, 'tanse', []);
                            $studentHafalan = collect(data_get($row, 'student_hafalan', []));
                            $studentMurajaah = collect(data_get($row, 'student_murajaah', []));
                            $studentTargets = collect(data_get($row, 'student_targets', []));

                            $pct = min(100, max(0, (float) data_get($row, 'progress_percent', 0)));
                            $strokeDash = 2 * 3.14159 * 44;
                            $offset = $strokeDash - ($pct / 100) * $strokeDash;
                        @endphp

                        <div x-show="activeChild === {{ $idx }}" x-transition class="space-y-3.5 sm:space-y-5" x-data="{ childTab: 'tahfizh' }">
                            
                            {{-- Header Profil Ananda & Quick Action --}}
                            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3 sm:p-5 shadow-xs sm:shadow-sm space-y-3">
                                <div class="flex items-center justify-between gap-2 flex-wrap sm:flex-nowrap border-b border-zinc-100 dark:border-zinc-800 pb-2.5 sm:pb-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-black text-sm sm:text-lg shrink-0">
                                            {{ substr(data_get($row, 'student_name', 'A'), 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <h3 class="text-sm sm:text-lg font-black text-zinc-900 dark:text-white truncate">
                                                    {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                                </h3>
                                                <span class="px-1.5 py-0.5 rounded-md text-[10px] sm:text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                    Kelas {{ data_get($row, 'class_room_name', '-') }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 font-medium truncate">
                                                NIS: <strong class="text-zinc-700 dark:text-zinc-300">{{ data_get($row, 'student_number', '-') }}</strong> · 
                                                <span>{{ $isUmmi ? 'Metode Ummi (Kls 10)' : 'Reguler Tahfizh' }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0 ml-auto sm:ml-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-bold text-[11px] transition border border-zinc-200 dark:border-zinc-700">
                                            <span>📖 Mushaf</span>
                                        </a>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                                            <span>📊 Rapor</span> →
                                        </a>
                                    </div>
                                </div>

                                {{-- 📱 TAMPILAN KHUSUS HANDPHONE: 3 HAL UTAMA MINIMALIS & TERTATA 📱 --}}
                                <div class="grid grid-cols-3 gap-2 sm:hidden">
                                    {{-- Hal 1: Tahfizh Compact Card --}}
                                    <button @click="childTab = 'tahfizh'"
                                            :class="childTab === 'tahfizh' ? 'border-teal-500 bg-teal-500/10 shadow-xs' : 'border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-850/50'"
                                            class="p-2.5 rounded-xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="text-[10px] font-bold text-teal-700 dark:text-teal-400 flex items-center gap-0.5">
                                                <span>📖</span> Tahfizh
                                            </span>
                                            <span class="text-[9px] font-black px-1 rounded bg-teal-500/15 text-teal-700 dark:text-teal-300">{{ round($pct) }}%</span>
                                        </div>
                                        <div class="my-0.5">
                                            <p class="text-sm font-black text-zinc-900 dark:text-white leading-tight">
                                                {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz' }}
                                            </p>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1 mt-1 overflow-hidden">
                                            <div class="bg-teal-500 h-1 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </button>

                                    {{-- Hal 2: Adab Compact Card --}}
                                    <button @click="childTab = 'adab'"
                                            :class="childTab === 'adab' ? 'border-amber-500 bg-amber-500/10 shadow-xs' : 'border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-850/50'"
                                            class="p-2.5 rounded-xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 flex items-center gap-0.5">
                                                <span>🕌</span> Adab
                                            </span>
                                            <span class="text-[9px] font-black px-1 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300">Grade {{ data_get($adabData, 'grade', 'A') }}</span>
                                        </div>
                                        <div class="my-0.5">
                                            <p class="text-sm font-black text-zinc-900 dark:text-white leading-tight">
                                                {{ data_get($adabData, 'final_score', '-') }} <span class="text-[10px] font-normal text-zinc-400">/ 100</span>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-0.5 mt-1">
                                            @php $isFilled = (bool) data_get($adabData, 'today_record'); @endphp
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                                            <span class="text-[8px] text-zinc-400 ml-0.5">{{ $isFilled ? '5 Shalat' : 'Shalat' }}</span>
                                        </div>
                                    </button>

                                    {{-- Hal 3: Kedisiplinan Compact Card --}}
                                    <button @click="childTab = 'tanse'"
                                            :class="childTab === 'tanse' ? 'border-purple-500 bg-purple-500/10 shadow-xs' : 'border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-850/50'"
                                            class="p-2.5 rounded-xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 flex items-center gap-0.5">
                                                <span>⭐</span> Disiplin
                                            </span>
                                            <span class="text-[9px] font-black px-1 rounded bg-purple-500/15 text-purple-700 dark:text-purple-300">
                                                {{ data_get($tanseData, 'violation_points', 0) > 0 ? '-'.data_get($tanseData, 'violation_points', 0) : 'Tertib' }}
                                            </span>
                                        </div>
                                        <div class="my-0.5">
                                            <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                                                +{{ data_get($tanseData, 'reward_points', 0) }} <span class="text-[10px] font-normal text-zinc-400">Poin</span>
                                            </p>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1 mt-1 overflow-hidden">
                                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-1 rounded-full" style="width: {{ min(100, max(20, (int) data_get($tanseData, 'reward_points', 0) * 2)) }}%"></div>
                                        </div>
                                    </button>
                                </div>

                                {{-- 💻 TAMPILAN TABLET & DESKTOP (BENTO GRID 3 HAL) 💻 --}}
                                <div class="hidden sm:grid sm:grid-cols-3 gap-4 lg:gap-5">
                                    
                                    {{-- 1. Tahfizh Circular Progress Bento --}}
                                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden border border-teal-500/20 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400 bg-teal-500/10 px-2.5 py-1 rounded-xl">
                                                <span>📖</span> {{ "Tahfizh Al-Qur'an" }}
                                            </span>
                                            <span class="text-xs font-bold px-2 py-0.5 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
                                                {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}%
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-center my-2">
                                            <div class="relative w-28 h-28 lg:w-32 lg:h-32 flex items-center justify-center">
                                                <svg class="w-full h-full transform -rotate-90 glow-teal-ring" viewBox="0 0 100 100">
                                                    <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="8" class="text-zinc-200 dark:text-zinc-800" fill="none" />
                                                    <circle cx="50" cy="50" r="44" stroke="url(#tealGradient{{ $idx }})" stroke-width="8" stroke-dasharray="{{ $strokeDash }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" fill="none" />
                                                    <defs>
                                                        <linearGradient id="tealGradient{{ $idx }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" stop-color="#0d9488" />
                                                            <stop offset="100%" stop-color="#10b981" />
                                                        </linearGradient>
                                                    </defs>
                                                </svg>
                                                <div class="absolute flex flex-col items-center justify-center text-center">
                                                    <span class="text-xl lg:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">{{ round($pct) }}%</span>
                                                    <span class="text-[9px] font-bold text-teal-600 dark:text-teal-400 uppercase">Target</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2 pt-2.5 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                                            <div>
                                                <p class="font-bold text-zinc-900 dark:text-white">
                                                    {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz Lengkap' }}
                                                </p>
                                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">Status: {{ $statusLabel }}</p>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $pct >= 80 ? 'bg-emerald-500 text-white' : 'bg-amber-500/20 text-amber-700 dark:text-amber-300' }}">
                                                {{ $pct >= 80 ? 'Tuntas' : 'Berjalan' }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- 2. Adab Assessment & Prayer Checklist Bento --}}
                                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden border border-amber-500/20 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-xl">
                                                <span>🕌</span> Karakter &amp; Adab
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg {{ data_get($adabData, 'today_record') ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300' }}">
                                                {{ data_get($adabData, 'today_record') ? '✓ Hari Ini Terisi' : 'Belum Terisi' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3 my-2">
                                            <div class="w-14 h-14 rounded-2xl gold-medal-badge flex flex-col items-center justify-center text-zinc-950 shrink-0 shadow-md border border-amber-300/40">
                                                <span class="text-[10px] font-black uppercase tracking-tight">Grade</span>
                                                <span class="text-lg font-black leading-none">{{ data_get($adabData, 'grade', 'A') }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-sm font-black text-zinc-900 dark:text-white truncate">
                                                    {{ data_get($adabData, 'grade') === 'A' ? 'Mumtaz (Istimewa)' : (data_get($adabData, 'grade') === 'B' ? 'Jayyid Jiddan' : 'Pembiasaan Baik') }}
                                                </h4>
                                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mt-0.5">
                                                    Skor: <strong class="text-zinc-900 dark:text-white font-black">{{ data_get($adabData, 'final_score', '-') }}</strong> / 100
                                                </p>
                                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate mt-0.5">
                                                    Ibadah shalat &amp; santun
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Prayer Checklist Pills --}}
                                        <div class="mt-2 pt-2.5 border-t border-zinc-100 dark:border-zinc-800/80">
                                            <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1.5">Checklist Shalat Fardhu Hari Ini:</p>
                                            <div class="grid grid-cols-5 gap-1 text-center text-[9px] font-bold">
                                                @php $isFilled = (bool) data_get($adabData, 'today_record'); @endphp
                                                <div class="py-0.5 rounded {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Subuh</div>
                                                <div class="py-0.5 rounded {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Dzuhur</div>
                                                <div class="py-0.5 rounded {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Ashar</div>
                                                <div class="py-0.5 rounded {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Maghrib</div>
                                                <div class="py-0.5 rounded {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Isya</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. Tanse Discipline & Points Bento --}}
                                    <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden border border-purple-500/20 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 bg-purple-500/10 px-2.5 py-1 rounded-xl">
                                                <span>⭐</span> Kedisiplinan &amp; Prestasi
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg {{ data_get($tanseData, 'violation_points', 0) > 0 ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300' : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' }}">
                                                {{ data_get($tanseData, 'violation_points', 0) > 0 ? 'Perlu Perhatian' : 'Teladan' }}
                                            </span>
                                        </div>

                                        <div class="my-2">
                                            <div class="flex items-baseline gap-1.5">
                                                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                                                    +{{ data_get($tanseData, 'reward_points', 0) }}
                                                </span>
                                                <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400">Poin Reward</span>
                                            </div>
                                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 mt-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-1.5 rounded-full" style="width: {{ min(100, max(20, (int) data_get($tanseData, 'reward_points', 0) * 2)) }}%"></div>
                                            </div>
                                        </div>

                                        <div class="mt-2 pt-2.5 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                                            <div>
                                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400">Pelanggaran:</span>
                                                <span class="font-bold text-rose-600 dark:text-rose-400 block">{{ data_get($tanseData, 'violation_points', 0) }} Kasus</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400">Status Sikap:</span>
                                                <span class="font-bold text-emerald-700 dark:text-emerald-400 block">Sangat Tertib</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- Navigation Sub-Tabs per Child (Minimalist Segmented Control) --}}
                            <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-xl overflow-x-auto scrollbar-none">
                                <button @click="childTab = 'tahfizh'"
                                        :class="childTab === 'tahfizh' ? 'bg-white dark:bg-zinc-900 text-teal-700 dark:text-teal-300 shadow-xs font-black' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-semibold'"
                                        class="flex-1 py-1.5 sm:py-2 px-2.5 sm:px-4 rounded-lg text-xs transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>📖</span> <span>Tahfizh &amp; Target</span>
                                </button>
                                <button @click="childTab = 'adab'"
                                        :class="childTab === 'adab' ? 'bg-white dark:bg-zinc-900 text-amber-700 dark:text-amber-300 shadow-xs font-black' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-semibold'"
                                        class="flex-1 py-1.5 sm:py-2 px-2.5 sm:px-4 rounded-lg text-xs transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>🕌</span> <span>Catatan Adab</span>
                                </button>
                                <button @click="childTab = 'tanse'"
                                        :class="childTab === 'tanse' ? 'bg-white dark:bg-zinc-900 text-purple-700 dark:text-purple-300 shadow-xs font-black' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-semibold'"
                                        class="flex-1 py-1.5 sm:py-2 px-2.5 sm:px-4 rounded-lg text-xs transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>⭐</span> <span>Kedisiplinan</span>
                                </button>
                            </div>

                            {{-- ═══════════════ HAL 1: TAHFIZH & TARGET ═══════════════ --}}
                            <div x-show="childTab === 'tahfizh'" x-transition class="space-y-3 sm:space-y-5">
                                
                                @if ($isUmmi)
                                    {{-- PROGRAM UMMI DETAILS (Minimalis 3 Kolom) --}}
                                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Jilid &amp; Hal
                                            </p>
                                            <p class="text-sm sm:text-xl font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">Hal {{ data_get($row, 'ummi_halaman', '-') }}</p>
                                        </div>

                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Target Ummi
                                            </p>
                                            <p class="text-xs sm:text-base font-black text-zinc-900 dark:text-white truncate">
                                                @if(data_get($row, 'ummi_target.ummi_jilid'))
                                                    {{ data_get($row, 'ummi_target.ummi_jilid') }}
                                                @elseif(data_get($row, 'ummi_target.surah.name_latin'))
                                                    {{ data_get($row, 'ummi_target.surah.name_latin') }}
                                                @else
                                                    Sesuai Jilid
                                                @endif
                                            </p>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                                                @if(data_get($row, 'ummi_target.halaman_peraga') || data_get($row, 'ummi_target.halaman_buku'))
                                                    Buku: {{ data_get($row, 'ummi_target.halaman_buku', '-') }}
                                                @else
                                                    Tahsin Ummi
                                                @endif
                                            </p>
                                        </div>

                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Munaqasyah
                                            </p>
                                            <p class="text-sm sm:text-xl font-black text-zinc-900 dark:text-white truncate">
                                                {{ data_get($row, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($row, 'ummi_munaqasyah_score'), 1) : '-' }}
                                            </p>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">Tajwid &amp; fashahah</p>
                                        </div>
                                    </div>
                                @else
                                    {{-- PROGRAM REGULER DETAILS (Minimalis 3 Kolom) --}}
                                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Target Harian
                                            </p>
                                            <p class="text-sm sm:text-xl font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'level_baris', 5) }} Baris</p>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">Per hari</p>
                                        </div>

                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Bulan Ini
                                            </p>
                                            <p class="text-sm sm:text-xl font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'capaian_baris_month', 0) }} Baris</p>
                                            <p class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 mt-0.5 truncate">{{ data_get($row, 'reguler_baris_percent', 0) }}% target</p>
                                        </div>

                                        <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-4 shadow-xs">
                                            <p class="text-[9px] sm:text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">
                                                Juz Lengkap
                                            </p>
                                            <p class="text-sm sm:text-xl font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'completed_juz_count', 0) }} Juz</p>
                                            <p class="text-[10px] sm:text-xs text-purple-600 dark:text-purple-400 mt-0.5 truncate">{{ data_get($row, 'completed_juz_list', 'Belum ada') }}</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Kurikulum Progress Bar --}}
                                <div class="rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3 sm:p-5 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs sm:text-sm font-black text-zinc-800 dark:text-zinc-200">🎯 Target Kurikulum</span>
                                                @if (data_get($row, 'target_juz_label'))
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-black bg-emerald-600 text-white">
                                                        {{ data_get($row, 'target_juz_label') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                                                Ketercapaian hafalan standar kurikulum sekolah.
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400">
                                                {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}%
                                            </p>
                                            <p class="text-[9px] sm:text-[10px] font-bold text-zinc-500 dark:text-zinc-400">
                                                {{ number_format(data_get($row, 'memorized_ayahs', 0)) }} ayat
                                            </p>
                                        </div>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300"
                                             style="width: {{ min(100, max(0, (float) data_get($row, 'progress_percent', 0))) }}%"></div>
                                    </div>
                                </div>

                                {{-- Peta Perjalanan Milestone (Touch-swipe friendly) --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-5 shadow-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-[11px] sm:text-xs font-black uppercase text-zinc-500 dark:text-zinc-400 tracking-wider">
                                            🗺️ Peta Perjalanan Target (4 Term)
                                        </h4>
                                        <span class="text-[10px] text-zinc-400 sm:hidden">Geser tabel &rarr;</span>
                                    </div>
                                    <x-student-hafalan-journey :milestones="data_get($row, 'term_milestones', [])" />
                                </div>

                                {{-- Riwayat Terakhir Hafalan & Murajaah --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                    {{-- Hafalan Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs space-y-2">
                                        <h4 class="text-xs font-black text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                            <span>📖</span> Setoran Hafalan Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @forelse ($studentHafalan as $h)
                                                <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between text-xs gap-2">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-zinc-900 dark:text-white truncate">
                                                            {{ $h->surah?->name_latin }} (Ayat {{ $h->ayah_start }}-{{ $h->ayah_end }})
                                                        </p>
                                                        <p class="text-[10px] text-zinc-400 mt-0.5">
                                                            {{ $h->submitted_at ? $h->submitted_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $h->status === 'passed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800' }} shrink-0">
                                                        {{ $h->status === 'passed' ? 'Lulus' : 'Ulang' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-400 py-2.5 text-center">Belum ada riwayat setoran.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Murajaah Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs space-y-2">
                                        <h4 class="text-xs font-black text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                            <span>🔄</span> Setoran Muraja'ah Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @forelse ($studentMurajaah as $m)
                                                <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between text-xs gap-2">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-zinc-900 dark:text-white truncate">
                                                            {{ $m->surah?->name_latin }} (Ayat {{ $m->ayah_start }}-{{ $m->ayah_end }})
                                                        </p>
                                                        <p class="text-[10px] text-zinc-400 mt-0.5">
                                                            {{ $m->reviewed_at ? $m->reviewed_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0">
                                                        Nilai: {{ $m->overall_score ?? '-' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-400 py-2.5 text-center">Belum ada riwayat muraja'ah.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ HAL 2: CATATAN ADAB & KARAKTER ═══════════════ --}}
                            <div x-show="childTab === 'adab'" x-transition class="space-y-3 sm:space-y-5">
                                {{-- 3 Kolom Indeks Nilai Adab --}}
                                <div class="grid grid-cols-3 gap-2 sm:gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Skor Adab</p>
                                        <p class="text-sm sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'final_score', 0) }}</p>
                                        <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 font-semibold truncate">Grade {{ data_get($adabData, 'grade', '-') }}</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Kehadiran</p>
                                        <p class="text-sm sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'attendance_rate', 0) }}%</p>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 font-semibold truncate">Pengisian angket</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Pembimbing</p>
                                        <p class="text-sm sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'mentor_score') !== null ? data_get($adabData, 'mentor_score') : '-' }}</p>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 font-semibold truncate">Nilai musyrif</p>
                                    </div>
                                </div>

                                {{-- Checklist Shalat Fardhu 5 Waktu Hari Ini --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                            <span>🕌</span> Mutaba'ah Shalat Fardhu 5 Waktu Hari Ini
                                        </h4>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ data_get($adabData, 'today_record') ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300' }}">
                                            {{ data_get($adabData, 'today_record') ? '✓ Terisi' : 'Belum Terisi' }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-5 gap-1 text-center text-[10px] font-bold">
                                        @php $isFilled = (bool) data_get($adabData, 'today_record'); @endphp
                                        <div class="p-1.5 rounded-lg {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Subuh</div>
                                        <div class="p-1.5 rounded-lg {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Dzuhur</div>
                                        <div class="p-1.5 rounded-lg {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Ashar</div>
                                        <div class="p-1.5 rounded-lg {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Maghrib</div>
                                        <div class="p-1.5 rounded-lg {{ $isFilled ? 'prayer-pill-done' : 'prayer-pill-pending' }}">Isya</div>
                                    </div>
                                </div>

                                {{-- Lembar Mutaba'ah Link Card --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">Buku Mutaba'ah Adab &amp; Karakter Ananda</h4>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                            Detail shalat berjamaah, dhuha, rawatib, tilawah, dan pembiasaan adab harian.
                                        </p>
                                    </div>
                                    <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition shadow-xs shrink-0">
                                        <span>Buka Lembar Adab</span> →
                                    </a>
                                </div>
                            </div>

                            {{-- ═══════════════ HAL 3: KEDISIPLINAN & PRESTASI (TANSE) ═══════════════ --}}
                            <div x-show="childTab === 'tanse'" x-transition class="space-y-3 sm:space-y-5">
                                {{-- 3 Kolom Poin Kedisiplinan --}}
                                <div class="grid grid-cols-3 gap-2 sm:gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Reward</p>
                                        <p class="text-sm sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">+{{ data_get($tanseData, 'reward_points', 0) }}</p>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 font-semibold truncate">Apresiasi</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Pelanggaran</p>
                                        <p class="text-sm sm:text-2xl font-black text-rose-600 dark:text-rose-400">-{{ data_get($tanseData, 'violation_points', 0) }}</p>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 font-semibold truncate">Tata tertib</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs">
                                        <p class="text-[9px] sm:text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-0.5 sm:mb-1 truncate">Net Poin</p>
                                        <p class="text-sm sm:text-2xl font-black {{ data_get($tanseData, 'net_points', 0) >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                                            {{ data_get($tanseData, 'net_points', 0) > 0 ? '+' : '' }}{{ data_get($tanseData, 'net_points', 0) }}
                                        </p>
                                        <p class="text-[10px] sm:text-xs text-zinc-500 font-semibold truncate">Skor akhir</p>
                                    </div>
                                </div>

                                {{-- Riwayat Catatan Poin Ananda --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2">
                                        <h4 class="text-xs font-black text-zinc-800 dark:text-zinc-200">
                                            Catatan Kedisiplinan &amp; Prestasi
                                        </h4>
                                        <a href="{{ route('student-points.index') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Lihat Semua →
                                        </a>
                                    </div>

                                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @forelse (data_get($tanseData, 'recent_points', []) as $pt)
                                            @php $isV = \App\Models\StudentPoint::isViolationType($pt->type); @endphp
                                            <div class="py-2 first:pt-0 last:pb-0 flex items-start justify-between gap-2 text-xs">
                                                <div class="space-y-0.5 min-w-0">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="font-bold text-zinc-900 dark:text-white truncate">{{ $pt->title }}</span>
                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase {{ $isV ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                                                            {{ \App\Models\StudentPoint::getTypeLabel($pt->type) }}
                                                        </span>
                                                    </div>
                                                    @if($pt->description)
                                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $pt->description }}</p>
                                                    @endif
                                                    <p class="text-[9px] text-zinc-400">
                                                        {{ $pt->date ? $pt->date->format('d M Y') : '-' }}
                                                        @if($pt->sanction)
                                                            · <strong class="text-amber-600">Sanksi: {{ $pt->sanction }}</strong>
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="text-xs font-black {{ $isV ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} shrink-0">
                                                    {{ $isV ? '-' : '+' }}{{ $pt->points }} Poin
                                                </span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-zinc-400 py-2.5 text-center">
                                                Alhamdulillah, belum ada catatan pelanggaran tata tertib ananda.
                                            </p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ FITUR PENDUKUNG (MUSHAF & RAPOR) ═══════════════ --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-4 pt-1">
                                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200/80 dark:border-emerald-800/50 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs flex items-center justify-between gap-2.5">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-1 text-emerald-800 dark:text-emerald-300 font-bold text-xs">
                                            <span>📖</span> Mushaf Al-Qur'an Digital
                                        </div>
                                        <p class="text-[10px] text-emerald-900/80 dark:text-emerald-200/80 line-clamp-1">
                                            Simak tilawah dan dampingi hafalan ananda di rumah.
                                        </p>
                                    </div>
                                    <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition shadow-xs shrink-0">
                                        <span>Buka</span> →
                                    </a>
                                </div>

                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/20 border border-indigo-200/80 dark:border-indigo-800/50 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-xs flex items-center justify-between gap-2.5">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-1 text-indigo-800 dark:text-indigo-300 font-bold text-xs">
                                            <span>📊</span> Rekapitulasi Rapor Digital
                                        </div>
                                        <p class="text-[10px] text-indigo-900/80 dark:text-indigo-200/80 line-clamp-1">
                                            Arsip capaian hafalan dan nilai ujian tahfizh.
                                        </p>
                                    </div>
                                    <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg transition shadow-xs shrink-0">
                                        <span>Buka</span> →
                                    </a>
                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>
</x-app-layout>