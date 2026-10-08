<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-arrows-up-down class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span>Urutan Hafalan · {{ $student->name }}</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                {{ $student->classRoom?->name }} · Juz diurutkan sesuai arah hafalan murid. Urutan di dalam juz terdeteksi otomatis dari setoran
                (surah makin kecil = dari akhir juz) dan bisa dikoreksi; urutan ini dipakai untuk menilai ketuntasan target.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <a href="{{ route('hafalan-targets.term', ['class_room_id' => $student->class_room_id]) }}" class="text-sm font-semibold text-indigo-600 hover:underline">&larr; Kembali ke Target Triwulan</a>
                <form method="POST" action="{{ route('hafalan-targets.direction', $student) }}" class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Arah hafalan</label>
                    <select name="hafalan_direction" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @foreach (\App\Support\HafalanOrder::directionOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(\App\Support\HafalanOrder::normalizeDirection($student->hafalan_direction) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Hafalan sebelum aplikasi (StudentPriorHafalan): dihitung sudah hafal untuk target, capaian ayat baru
                 & progress, tapi bukan setoran (tidak masuk riwayat/grafik, tidak menambah capaian triwulan). --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm p-5 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Hafalan Sebelum Aplikasi</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                        Hafalan murid dari sebelum aplikasi dipakai (mis. tahun lalu). Dianggap sudah hafal: target melewatinya dan setoran ulang
                        ayat ini tidak dihitung ayat baru. Bukan setoran, jadi tidak menambah capaian triwulan mana pun.
                        Untuk satu juz penuh, pakai tombol <strong>Tandai hafal penuh</strong> di tabel.
                    </p>
                </div>

                @if ($priorGroups !== [])
                    <div class="flex flex-wrap gap-2">
                        @foreach ($priorGroups as $group)
                            @php
                                // Juz penuh = satu chip; selain itu tiap rentang.
                                $chips = $group['full']
                                    ? [['label' => 'Juz '.$group['juz'].' (penuh)', 'field' => 'juz', 'value' => $group['juz']]]
                                    : $group['entries']->map(fn ($p) => ['label' => $p->surah->name_latin.' '.$p->ayah_start.'–'.$p->ayah_end, 'field' => 'id', 'value' => $p->id])->all();
                            @endphp
                            @foreach ($chips as $chip)
                                <span class="inline-flex items-center gap-1.5 rounded-lg {{ $group['full'] ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }} px-2.5 py-1 text-xs font-semibold">
                                    {{ $chip['label'] }}
                                    @if ($canEditPrior)
                                        <form method="POST" action="{{ route('hafalan-targets.prior.destroy', $student) }}" class="inline"
                                              onsubmit="return confirm('Hapus {{ $chip['label'] }} dari hafalan sebelum aplikasi?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="{{ $chip['field'] }}" value="{{ $chip['value'] }}">
                                            <button type="submit" title="Hapus" class="opacity-60 hover:opacity-100 hover:text-rose-600">&times;</button>
                                        </form>
                                    @endif
                                </span>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400">Belum ada.</p>
                @endif

                @if ($canEditPrior)
                    <form method="POST" action="{{ route('hafalan-targets.prior.store', $student) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Surah</label>
                            <select name="surah_id" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                                <option value="">Pilih surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected((string) old('surah_id') === (string) $surah->id)>{{ $surah->number }}. {{ $surah->name_latin }} ({{ $surah->total_ayah }} ayat)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Ayat awal</label>
                            <input type="number" name="ayah_start" min="1" required value="{{ old('ayah_start') }}" class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        </div>
                        <div class="w-24">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Ayat akhir</label>
                            <input type="number" name="ayah_end" min="1" required value="{{ old('ayah_end') }}" class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Tambah</button>
                    </form>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Juz</th>
                            <th class="px-4 py-3 text-left">Cakupan</th>
                            <th class="px-4 py-3 text-left">Urutan di dalam juz</th>
                            <th class="px-4 py-3 text-left">Hafalan sebelum aplikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($juzRows as $index => $row)
                            <tr class="{{ $row['setoran_count'] > 0 || $row['prior_count'] > 0 ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2.5 text-gray-400 font-bold">{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5">
                                    <p class="font-bold text-gray-900 dark:text-white">Juz {{ $row['juz'] }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $surahNames[$row['surah_range'][0]] ?? $row['surah_range'][0] }} – {{ $surahNames[$row['surah_range'][1]] ?? $row['surah_range'][1] }}</p>
                                </td>
                                <td class="px-4 py-2.5 min-w-[180px]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
                                            <div class="h-full rounded-full {{ $row['covered_percent'] >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $row['covered_percent'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 dark:text-zinc-300 w-10 text-right">{{ $row['covered_percent'] }}%</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $row['setoran_count'] }} setoran lulus{{ $row['prior_count'] > 0 ? ' · '.$row['prior_count'].' rentang hafalan lama' : '' }}</p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <form method="POST" action="{{ route('hafalan-targets.juz-order', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="juz" value="{{ $row['juz'] }}">
                                        <select name="order" onchange="this.form.submit()" class="rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5 pl-2 pr-8">
                                            <option value="auto" @selected($row['source'] !== 'manual')>
                                                {{ $row['order'] === 'desc' ? 'Dari akhir juz' : 'Dari awal juz' }} ({{ ['auto' => 'otomatis dari setoran', 'grade' => 'aturan Kelas 11 & 12'][$row['source']] ?? 'default' }})
                                            </option>
                                            <option value="asc" @selected($row['source'] === 'manual' && $row['order'] === 'asc')>Dari awal juz (diatur)</option>
                                            <option value="desc" @selected($row['source'] === 'manual' && $row['order'] === 'desc')>Dari akhir juz (diatur)</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if ($canEditPrior)
                                        @if ($row['covered_percent'] < 100)
                                            <form method="POST" action="{{ route('hafalan-targets.prior.store', $student) }}"
                                                  onsubmit="return confirm('Catat seluruh Juz {{ $row['juz'] }} sebagai sudah hafal sebelum aplikasi?')">
                                                @csrf
                                                <input type="hidden" name="juz" value="{{ $row['juz'] }}">
                                                <button type="submit" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Tandai hafal penuh</button>
                                            </form>
                                        @endif
                                        @if ($row['prior_count'] > 0)
                                            <form method="POST" action="{{ route('hafalan-targets.prior.destroy', $student) }}"
                                                  onsubmit="return confirm('Hapus hafalan sebelum aplikasi di Juz {{ $row['juz'] }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="juz" value="{{ $row['juz'] }}">
                                                <button type="submit" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">Hapus hafalan lama juz ini</button>
                                            </form>
                                        @endif
                                    @elseif ($row['prior_count'] > 0)
                                        <span class="text-xs text-gray-500">{{ $row['prior_count'] }} rentang</span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
