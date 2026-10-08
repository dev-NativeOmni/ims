{{--
    Tabel isian target per kelas, dipakai Target Triwulan (3 kolom bulan) dan Target Bulanan untuk
    Kelas 11 & 12 (satu kolom bulan, $month terisi). Data: HafalanTargetController::targetGrid() ($grid).
    Disimpan lewat hafalan-targets.term.store; mode bulanan hanya menyimpan bulan itu.
--}}
@php extract($grid); @endphp

            {{-- Filter: Target Triwulan memilih triwulan, Target Bulanan memilih bulan. --}}
            <form method="GET" action="{{ $month ? route('hafalan-targets.index') : route('hafalan-targets.term') }}" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 shadow-sm flex flex-col sm:flex-row gap-3">
                @if ($month)
                    <input type="hidden" name="program" value="reguler">
                    <select name="month" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                        @foreach ($monthChoices as $value => $label)
                            <option value="{{ $value }}" @selected($value === $month)>{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="period" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}" @selected($value === $period)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="teacher_id" onchange="this.form.submit()" @disabled($teachers->count() <= 1 && $isTeacherOnly) class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @if (! $isTeacherOnly)
                        <option value="">Semua Guru Pengampu / Halaqah</option>
                    @endif
                    @forelse ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($teacher->id === (int) $currentTeacherId)>Halaqah {{ $teacher->user?->name ?? 'Musyrif #'.$teacher->id }}</option>
                    @empty
                        <option value="">Tidak ada halaqah</option>
                    @endforelse
                </select>
                @if ($teachers->count() <= 1 && $currentTeacherId && $isTeacherOnly)
                    <input type="hidden" name="teacher_id" value="{{ $currentTeacherId }}">
                @endif
                <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @forelse ($classRooms as $class)
                        <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->name }} ({{ $class->program?->name }})</option>
                    @empty
                        <option value="">Tidak ada kelas 11/12</option>
                    @endforelse
                </select>
            </form>

            {{-- Ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ([
                    ['Murid', $summary['students'].' murid', 'text-gray-900 dark:text-white'],
                    [$month ? 'Sudah Ada Target Bulan Ini' : 'Sudah Ada Target', $summary['with_target'].' / '.$summary['students'].' murid', 'text-amber-600 dark:text-amber-400'],
                    [$month ? 'Baris Tuntas Bulan Ini' : 'Target Baris Tuntas', $summary['reached'].' / '.$summary['students'].' murid', 'text-emerald-600 dark:text-emerald-400'],
                    [$month ? 'Rata-rata Progres Bulan Ini' : 'Rata-rata Progres', $summary['avg_progress'].'%', 'text-indigo-600 dark:text-indigo-400'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $label }}</p>
                        <p class="mt-1 text-xl font-extrabold {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('hafalan-targets.term.store') }}" x-data="termTargets(@js($surahs->pluck('total_ayah', 'id')))">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                @if ($month)
                    {{-- Hanya bulan ini yang disimpan (HafalanTargetController::storeTerm). --}}
                    <input type="hidden" name="month" value="{{ $month }}">
                @endif
                <input type="hidden" name="class_room_id" value="{{ $selectedClass?->id }}">
                @if ($currentTeacherId)
                    <input type="hidden" name="teacher_id" value="{{ $currentTeacherId }}">
                @endif

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/60 text-gray-500 dark:text-zinc-400">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 dark:bg-zinc-800 px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider">Murid</th>
                                @foreach ($visibleMonths as $monthKey => $monthInfo)
                                    <th class="px-3 py-3 text-left min-w-[230px]">
                                        <p class="text-[11px] font-black uppercase tracking-wider">{{ $monthInfo['label'] }}</p>
                                        <p class="mt-0.5 text-[11px] font-semibold normal-case {{ $monthInfo['has_meeting'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                                            Deadline {{ $monthInfo['deadline']->locale('id')->translatedFormat('D, d M Y') }}
                                        </p>
                                        <p class="text-[10px] font-normal normal-case text-gray-400">
                                            {{ $monthInfo['has_meeting'] ? $monthInfo['meetings'].' pertemuan aktif' : 'Tidak ada pertemuan aktif' }}
                                        </p>
                                        @include('hafalan-targets.partials.month-lock', ['lock' => $locks->get($monthKey), 'classId' => $selectedClass?->id])
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider min-w-[190px]">Target Triwulan &amp; Capaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse ($rows as $row)
                                @php
                                    $student = $row['student'];
                                    $plan = $row['plan'];
                                @endphp
                                <tr class="align-top">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-3">
                                        <a href="{{ route('hafalan-targets.juz-orders', $student) }}" class="font-bold text-gray-900 dark:text-white hover:text-indigo-600 hover:underline" title="Lihat & atur urutan hafalan per juz">{{ $student->name }}</a>
                                        <p class="text-[11px] text-gray-500">{{ ucfirst($student->tahfizh_level ?? 'reguler') }}</p>
                                        @if ($plan['start'])
                                            <p class="text-[11px] text-gray-400" title="{{ ['history' => 'Belum setor di triwulan ini: lanjutan setoran terakhir ('.$plan['start']['date'].')', 'first_setoran' => 'Setoran pertama triwulan ('.$plan['start']['date'].')', 'default' => 'Belum ada setoran: awal urutan hafalan'][$plan['start']['source']] }}">
                                                Awal: {{ $plan['start']['surah_model']?->name_latin }} : {{ $plan['start']['ayah'] }}
                                                <span class="text-gray-300">· Juz {{ $plan['start']['juz'] }}</span>
                                            </p>
                                        @endif
                                    </td>
                                    @foreach ($visibleMonths as $monthKey => $monthInfo)
                                        @php
                                            $cell = $plan['months'][$monthKey] ?? [];
                                            $stored = $cell['target'] ?? null;
                                            $surahValue = (string) old("targets.{$student->id}.{$monthKey}.surah_id", $stored?->surah_id);
                                            $ayahValue = old("targets.{$student->id}.{$monthKey}.ayah", $stored?->ayah);
                                            $hasError = $errors->has("targets.{$student->id}.{$monthKey}");
                                            // Bulan terkunci: isian beku (tidak dikirim), status tetap otomatis.
                                            $locked = $locks->has($monthKey);
                                        @endphp
                                        <td class="px-3 py-3 {{ $locked ? 'bg-gray-50 dark:bg-zinc-800/40' : '' }}">
                                            <div class="flex gap-1.5">
                                                <select name="targets[{{ $student->id }}][{{ $monthKey }}][surah_id]" @disabled(! $canEdit || $locked)
                                                        x-on:change="syncMax($event.target)"
                                                        class="min-w-0 flex-1 rounded-lg text-xs py-1.5 pl-2 pr-7 dark:bg-zinc-800 dark:text-zinc-200 {{ $hasError ? 'border-rose-400' : 'border-gray-200 dark:border-zinc-700' }}">
                                                    <option value="">– Surah –</option>
                                                    @foreach ($surahs as $surah)
                                                        <option value="{{ $surah->id }}" @selected($surahValue === (string) $surah->id)>{{ $surah->option_label }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" min="1" name="targets[{{ $student->id }}][{{ $monthKey }}][ayah]" value="{{ $ayahValue }}" placeholder="Ayat" @disabled(! $canEdit || $locked)
                                                       x-init="syncMax($el.previousElementSibling)"
                                                       class="w-16 rounded-lg text-xs py-1.5 px-2 dark:bg-zinc-800 dark:text-zinc-200 {{ $hasError ? 'border-rose-400' : 'border-gray-200 dark:border-zinc-700' }}">
                                            </div>
                                            <p class="mt-1 text-[11px] text-gray-500">
                                                Target <span class="font-semibold text-gray-700 dark:text-zinc-300">{{ ($cell['target_lines'] ?? 0) + 0 }} baris</span>
                                                · Capaian <span class="font-semibold text-gray-700 dark:text-zinc-300">{{ ($cell['achieved_lines'] ?? 0) + 0 }} baris</span>
                                            </p>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @if (($cell['target_lines'] ?? 0) > 0 && $cell['reached'])
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[10px] font-bold">Baris tuntas</span>
                                                @elseif (($cell['target_lines'] ?? 0) > 0 && $monthInfo['end']->lt(today()))
                                                    <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-bold">Baris belum tuntas</span>
                                                @endif
                                                @if ($stored && ($cell['position']['position_reached'] ?? false))
                                                    <span class="px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 text-[10px] font-bold" title="Semua ayat sampai surah & ayat target sudah lulus disetor">Surah target tercapai</span>
                                                @endif
                                                @if ($stored?->auto_month !== null)
                                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-bold" title="Target lama dari perhitungan otomatis. Periksa lalu simpan untuk menjadikannya target guru.">Otomatis lama</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3">
                                        @if ($plan['target'])
                                            <p class="font-bold text-indigo-700 dark:text-indigo-400">{{ $plan['target']->surah?->name_latin }} : {{ $plan['target']->ayah }}</p>
                                            <p class="text-[11px] text-gray-500">dari target {{ $months[$plan['target_month']]['label'] ?? '' }}</p>
                                        @else
                                            <p class="text-xs text-amber-600">Belum ada target</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-gray-500">
                                            <span class="font-semibold text-gray-700 dark:text-zinc-300">{{ $plan['achieved_lines'] + 0 }}</span> dari
                                            <span class="font-semibold text-gray-700 dark:text-zinc-300">{{ $plan['target_lines'] + 0 }}</span> baris target
                                        </p>
                                        <p class="mt-1 text-[11px] text-gray-500">
                                            Setoran terakhir:
                                            <span class="font-semibold text-gray-700 dark:text-zinc-300">{{ $plan['capaian'] ? $plan['capaian']['surah']?->name_latin.' : '.$plan['capaian']['ayah'] : '–' }}</span>
                                        </p>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
                                                <div class="h-full rounded-full {{ $plan['reached'] ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $plan['progress'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700 dark:text-zinc-300">{{ $plan['progress'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($visibleMonths) + 2 }}" class="px-4 py-12 text-center text-gray-400">
                                        Tidak ada murid kelas 11/12 yang dapat ditampilkan. Target Kelas 10 (Ummi) dibuat oleh guru di Target Bulanan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($canEdit && $rows->isNotEmpty())
                    <div class="sticky bottom-24 xl:bottom-4 z-20 mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-2xl border border-gray-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 backdrop-blur px-4 py-3 shadow-lg">
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Kosongkan surah &amp; ayat untuk menghapus target bulan itu. Bulan terkunci tidak ikut diubah.</p>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm cursor-pointer">Simpan Target</button>
                    </div>
                @endif
            </form>

            @include('hafalan-targets.partials.month-lock-forms', ['classId' => $selectedClass?->id, 'months' => $visibleMonths])

    <script>
        function termTargets(totalAyah) {
            return {
                // Batas ayat input mengikuti jumlah ayat surah yang dipilih.
                syncMax(select) {
                    const input = select.nextElementSibling;
                    const max = totalAyah[select.value];
                    if (max) {
                        input.max = max;
                        input.placeholder = '1–' + max;
                    } else {
                        input.removeAttribute('max');
                        input.placeholder = 'Ayat';
                    }
                },
            };
        }
    </script>
