<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-bookmark class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                <span>Target Ummi</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Target bulanan murid Kelas 10: Jilid &amp; Halaman Buku, serta Surah &amp; Ayat (opsional). Isi serentak untuk satu halaqah,
                lalu ubah baris murid yang capaiannya berbeda. Deadline otomatis = hari aktif terakhir di bulan itu (Senin–Jumat, bukan libur),
                dan bisa diubah manual lewat tombol deadline.
            </p>
        </div>
    </x-slot>

    @php
        $statusBadge = fn (?string $status) => match ($status) {
            'completed' => ['Selesai', 'bg-emerald-100 text-emerald-700'],
            'missed' => ['Terlewat', 'bg-rose-100 text-rose-700'],
            'active' => ['Aktif', 'bg-sky-100 text-sky-700'],
            default => ['–', 'bg-gray-100 text-gray-400'],
        };
        $jilids = ['Jilid 1', 'Jilid 2', 'Jilid 3'];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('hafalan-targets.partials.period-tabs')

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                    <p class="font-bold">Target belum disimpan:</p>
                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Filter --}}
            <form method="GET" action="{{ route('hafalan-targets.ummi') }}" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 shadow-sm flex flex-col sm:flex-row gap-3">
                <select name="month" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $month)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="teacher_id" onchange="this.form.submit()" @disabled($teachers->count() <= 1) class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @forelse ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($teacher->id === $currentTeacherId)>Halaqah {{ $teacher->user?->name ?? 'Musyrif #'.$teacher->id }}</option>
                    @empty
                        <option value="">Tidak ada halaqah Kelas 10</option>
                    @endforelse
                </select>
                @if ($teachers->count() <= 1 && $currentTeacherId)
                    <input type="hidden" name="teacher_id" value="{{ $currentTeacherId }}">
                @endif
                <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    <option value="">Semua kelas di halaqah ini</option>
                    @foreach ($classRooms as $class)
                        <option value="{{ $class->id }}" @selected((int) request('class_room_id') === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                {{-- Deadline: otomatis, atau manual (lebih tinggi) bila diubah. Tersimpan saat klik Simpan Target. --}}
                <div class="sm:ml-auto self-center"
                     x-data="{
                         editing: false,
                         value: @js(old('deadline', $deadline->toDateString())),
                         auto: @js($autoDeadline->toDateString()),
                         label(date) {
                             return new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                         },
                     }">
                    <div x-show="! editing" class="flex items-center gap-2 rounded-xl bg-teal-50 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900 px-3 py-2 text-xs font-semibold text-teal-800 dark:text-teal-300">
                        <span>Deadline: <span x-text="label(value)">{{ $deadline->locale('id')->translatedFormat('l, d F Y') }}</span></span>
                        <span x-show="value !== auto" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Manual</span>
                        <span x-show="value === auto" class="rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-bold text-teal-700 dark:bg-teal-900 dark:text-teal-300">Otomatis</span>
                        @if ($canEdit && $students->isNotEmpty())
                            <button type="button" @click="editing = true" class="ml-1 text-teal-700 hover:text-teal-900 dark:text-teal-300 cursor-pointer" title="Ubah deadline">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                    @if ($canEdit && $students->isNotEmpty())
                        <div x-show="editing" style="display: none" class="rounded-xl bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-900 px-3 py-2 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <input type="date" name="deadline" form="ummi-target-form" x-model="value"
                                       min="{{ $monthStart->toDateString() }}" max="{{ $monthEnd->toDateString() }}"
                                       class="rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold py-1">
                                <button type="button" @click="value = auto" x-show="value !== auto" class="px-2 py-1 rounded-lg bg-white dark:bg-zinc-800 border border-teal-200 dark:border-teal-800 text-[11px] font-bold text-teal-700 dark:text-teal-300 cursor-pointer">Otomatis</button>
                                <button type="button" @click="editing = false" class="px-2 py-1 rounded-lg bg-teal-600 text-[11px] font-bold text-white cursor-pointer">OK</button>
                            </div>
                            <p class="text-[11px] text-teal-800 dark:text-teal-300">
                                Otomatis: <span x-text="label(auto)"></span>. Berlaku untuk murid di tabel ini setelah klik <b>Simpan Target</b>.
                            </p>
                        </div>
                    @endif
                </div>
            </form>

            <form method="POST" action="{{ route('hafalan-targets.ummi.store') }}" id="ummi-target-form">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="teacher_id" value="{{ $currentTeacherId }}">
                <input type="hidden" name="class_room_id" value="{{ request('class_room_id') }}">

                @if ($canEdit && $students->isNotEmpty())
                    {{-- Isi serentak: terapkan ke semua baris, lalu ubah murid yang berbeda. --}}
                    <div class="mb-4 rounded-2xl border border-teal-200 dark:border-teal-900 bg-teal-50/70 dark:bg-teal-950/20 p-4 shadow-sm">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-teal-800 dark:text-teal-300">Isi serentak untuk semua murid di tabel</p>
                        <div class="mt-2 flex flex-wrap items-end gap-2" data-bulk>
                            <select data-bulk-field="jilid" class="rounded-lg border-teal-200 text-xs py-1.5">
                                <option value="">Jilid</option>
                                @foreach ($jilids as $jilid)
                                    <option value="{{ $jilid }}">{{ $jilid }}</option>
                                @endforeach
                            </select>
                            <input data-bulk-field="halaman" type="number" min="1" max="{{ \App\Services\UmmiProgressService::PAGES_PER_JILID }}" placeholder="Hal. Buku" class="w-24 rounded-lg border-teal-200 text-xs py-1.5">
                            <select data-bulk-field="surah_id" class="rounded-lg border-teal-200 text-xs py-1.5 max-w-[200px]">
                                <option value="">– Surah (opsional) –</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}">{{ $surah->option_label }}</option>
                                @endforeach
                            </select>
                            <input data-bulk-field="ayah" type="number" min="1" placeholder="Ayat" class="w-20 rounded-lg border-teal-200 text-xs py-1.5">
                            <button type="button" onclick="ummiApplyBulk()" class="px-4 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold cursor-pointer">Terapkan ke semua baris</button>
                        </div>
                        <p class="mt-1.5 text-[11px] text-teal-700 dark:text-teal-400">Belum tersimpan sampai klik <strong>Simpan Target</strong>. Kosongkan satu baris untuk menghapus target murid itu di bulan ini.</p>
                    </div>
                @endif

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 dark:bg-zinc-800 px-4 py-3 text-left">Murid</th>
                                <th class="px-3 py-3 text-left">Posisi Sekarang</th>
                                <th class="px-3 py-3 text-left">Target Buku</th>
                                <th class="px-3 py-3 text-left">Target Hafalan Surah</th>
                                <th class="px-3 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse ($students as $student)
                                @php
                                    $target = $targets->get($student->id);
                                    $position = $positions[$student->id] ?? null;
                                    $old = fn ($field, $default) => old("targets.{$student->id}.{$field}", $default);
                                    $jilidValue = (string) $old('jilid', $target?->ummi_jilid);
                                    $pageValue = $old('halaman', $target ? \App\Support\AyahLabel::end($target->halaman_buku, '') : '');
                                    $surahValue = (string) $old('surah_id', $target?->surah_id);
                                    $ayahValue = $old('ayah', $target?->ayah);
                                    $hasError = $errors->has("targets.{$student->id}");
                                    [$bookLabel, $bookClass] = $statusBadge($target?->book_status);
                                    [$surahLabel, $surahClass] = $statusBadge($target?->surah_status);
                                @endphp
                                <tr class="align-top {{ $hasError ? 'bg-rose-50/60' : '' }}" data-ummi-row>
                                    <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-3">
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $student->name }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $student->classRoom?->name }}</p>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-xs">
                                        <p><span class="text-gray-400">Buku</span> <span class="font-semibold text-sky-700 dark:text-sky-400">{{ $position['book_label'] ?? '-' }}</span></p>
                                        <p><span class="text-gray-400">Hafal</span> <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ $position['hafalan_label'] ?? '-' }}</span></p>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="flex gap-1.5">
                                            <select name="targets[{{ $student->id }}][jilid]" data-field="jilid" @disabled(! $canEdit) class="rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5">
                                                <option value="">Jilid</option>
                                                @foreach ($jilids as $jilid)
                                                    <option value="{{ $jilid }}" @selected($jilidValue === $jilid)>{{ $jilid }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" name="targets[{{ $student->id }}][halaman]" data-field="halaman" value="{{ $pageValue }}" min="1" max="{{ \App\Services\UmmiProgressService::PAGES_PER_JILID }}" placeholder="Hal." @disabled(! $canEdit) class="w-20 rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5">
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="flex gap-1.5">
                                            <select name="targets[{{ $student->id }}][surah_id]" data-field="surah_id" @disabled(! $canEdit) class="max-w-[190px] rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5">
                                                <option value="">– Tanpa surah –</option>
                                                @foreach ($surahs as $surah)
                                                    <option value="{{ $surah->id }}" @selected($surahValue === (string) $surah->id)>{{ $surah->option_label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" name="targets[{{ $student->id }}][ayah]" data-field="ayah" value="{{ $ayahValue }}" min="1" placeholder="Ayat" @disabled(! $canEdit) class="w-16 rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5">
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-[10px] font-bold space-y-1">
                                        <p><span class="text-gray-400 font-semibold">Buku</span> <span class="ml-1 px-1.5 py-0.5 rounded {{ $bookClass }}">{{ $bookLabel }}</span></p>
                                        <p><span class="text-gray-400 font-semibold">Hafal</span> <span class="ml-1 px-1.5 py-0.5 rounded {{ $surahClass }}">{{ $surahLabel }}</span></p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">Tidak ada murid Kelas 10 di halaqah / kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($canEdit && $students->isNotEmpty())
                    <div class="sticky bottom-24 xl:bottom-4 z-20 mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-2xl border border-gray-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 backdrop-blur px-4 py-3 shadow-lg">
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Status Buku &amp; Hafalan diperbarui otomatis saat setoran Ummi disimpan dan setiap malam.</p>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-sm cursor-pointer">Simpan Target</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        // Salin isian serentak ke semua baris murid (hanya isian yang diisi).
        function ummiApplyBulk() {
            document.querySelectorAll('[data-bulk-field]').forEach((source) => {
                if (source.value === '') return;
                document.querySelectorAll('[data-ummi-row] [data-field="' + source.dataset.bulkField + '"]').forEach((input) => {
                    input.value = source.value;
                });
            });
        }
    </script>
</x-app-layout>
