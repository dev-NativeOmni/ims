<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">Impor Data Dapodik</h2>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Memperbarui NIS, NISN, rombel, tempat & tanggal lahir murid dari file Excel ekspor Dapodik.
                </p>
            </div>
            <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition self-start sm:self-auto">
                <x-heroicon-m-arrow-left class="w-4 h-4 shrink-0" />
                <span>Data Murid</span>
            </a>
        </div>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-4">
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm font-semibold">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl text-sm font-semibold">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl text-sm font-semibold">{{ $errors->first() }}</div>
            @endif

            @unless ($preview)
                <form method="POST" action="{{ route('students.dapodik.preview') }}" enctype="multipart/form-data"
                      class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                    @csrf
                    <div>
                        <label for="file" class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-2">File Excel Dapodik</label>
                        <input id="file" type="file" name="file" accept=".xlsx,.xls" required
                               class="block w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 dark:file:bg-zinc-800 file:px-3 file:py-2 file:text-xs file:font-bold">
                    </div>
                    <ul class="text-xs text-zinc-500 dark:text-zinc-400 list-disc pl-5 space-y-1">
                        <li>Kolom yang dibaca: <strong>Nama, NIPD/NIS, NISN, Tempat Lahir, Tanggal Lahir, Rombel</strong>. NIK dan kolom lain tidak dibaca atau disimpan.</li>
                        <li>Semua sheet dibaca; murid yang muncul di beberapa sheet dihitung sekali.</li>
                        <li>Belum ada yang disimpan sampai Anda memeriksa pratinjau dan menekan <strong>Simpan</strong>.</li>
                    </ul>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <x-heroicon-o-document-magnifying-glass class="w-4 h-4" /> Baca File &amp; Pratinjau
                    </button>
                </form>
            @else
                @php
                    $changed = collect($preview['matched'])->filter(fn ($m) => $m['changes'] !== []);
                    $formatValue = fn ($field, $value) => $field === 'birth_date' && $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : ($value ?? '–');
                @endphp

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ([
                        ['Cocok', count($preview['matched']), 'text-emerald-700 dark:text-emerald-400'],
                        ['Ada perubahan', $changed->count(), 'text-indigo-700 dark:text-indigo-400'],
                        ['Tidak cocok', count($preview['unmatched']), 'text-amber-700 dark:text-amber-400'],
                        ['Murid aktif tidak ada di file', $preview['missing']->count(), 'text-zinc-700 dark:text-zinc-300'],
                    ] as [$label, $count, $color])
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 shadow-sm">
                            <p class="text-2xl font-black {{ $color }}">{{ $count }}</p>
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('students.dapodik.apply') }}" class="space-y-4" x-data="{ onlyChanged: true }">
                    @csrf

                    {{-- Murid yang cocok otomatis --}}
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Cocok Otomatis</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Hilangkan centang bila pasangannya salah; baris itu tidak disimpan.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                <input type="checkbox" x-model="onlyChanged" class="rounded text-indigo-600"> Hanya yang ada perubahan
                            </label>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                                <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 w-10">Simpan</th>
                                        <th class="px-4 py-3">Dapodik</th>
                                        <th class="px-4 py-3">Murid di Aplikasi</th>
                                        <th class="px-4 py-3">Cocok lewat</th>
                                        <th class="px-4 py-3">Perubahan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($preview['matched'] as $match)
                                        <tr x-show="! onlyChanged || {{ $match['changes'] !== [] ? 'true' : 'false' }}" class="align-top {{ in_array($match['method'], ['Nama'], true) ? 'bg-amber-50/60 dark:bg-amber-950/10' : '' }}">
                                            <td class="px-4 py-3">
                                                {{-- Dicentang = disimpan; dikirim sebagai "skip" bila centang dihilangkan. --}}
                                                <input type="checkbox" checked class="rounded text-indigo-600"
                                                       onchange="this.nextElementSibling.disabled = this.checked">
                                                <input type="hidden" name="skip[]" value="{{ $match['index'] }}" disabled>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $match['name'] }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match['rombel'] ?? '-' }} · NISN {{ $match['nisn'] ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $match['student']->name }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match['student']->classRoom?->name ?? '-' }} · {{ $match['student']->student_number ?: '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold {{ $match['method'] === 'Nama' ? 'text-amber-700 dark:text-amber-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                {{ $match['method'] }}@if ($match['method'] === 'Nama') <span class="block font-normal">cek ulang</span>@endif
                                            </td>
                                            <td class="px-4 py-3 text-xs">
                                                @forelse ($match['changes'] as $field => [$old, $new])
                                                    <div><span class="text-zinc-500 dark:text-zinc-400">{{ $fieldLabels[$field] }}:</span> <span class="line-through text-zinc-400">{{ $formatValue($field, $old) }}</span> → <strong class="text-zinc-900 dark:text-white">{{ $formatValue($field, $new) }}</strong></div>
                                                @empty
                                                    <span class="text-zinc-400">Sudah sesuai</span>
                                                @endforelse
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Baris Dapodik tanpa pasangan: pilih manual --}}
                    @if ($preview['unmatched'] !== [])
                        <div class="bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-900/50 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Tidak Cocok Otomatis ({{ count($preview['unmatched']) }})</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Pilih murid aplikasi yang sesuai (urut dari nama paling mirip), atau biarkan "Lewati".</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3">Dapodik</th>
                                            <th class="px-4 py-3">Tgl lahir</th>
                                            <th class="px-4 py-3 min-w-[260px]">Pasangkan ke murid</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach ($preview['unmatched'] as $row)
                                            <tr class="align-top">
                                                <td class="px-4 py-3">
                                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $row['name'] }}</div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['rombel'] ?? '-' }} · NIS {{ $row['nis'] ?? '-' }} · NISN {{ $row['nisn'] ?? '-' }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $formatValue('birth_date', $row['birth_date']) }}</td>
                                                <td class="px-4 py-3">
                                                    <select name="manual[{{ $row['index'] }}]" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                                                        <option value="">— Lewati —</option>
                                                        @foreach ($row['suggestions'] as $student)
                                                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->classRoom?->name ?? '-' }}{{ $student->birth_date ? ', '.$student->birth_date->format('d/m/Y') : '' }})</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($preview['missing']->isNotEmpty())
                        <details class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm px-5 py-4">
                            <summary class="text-sm font-bold text-zinc-900 dark:text-white cursor-pointer">Murid aktif di aplikasi yang tidak ada di file ({{ $preview['missing']->count() }})</summary>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">Tidak diubah. Rapor cetak mereka tetap memakai data aplikasi.</p>
                            <ul class="mt-2 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-1 text-xs text-zinc-700 dark:text-zinc-300">
                                @foreach ($preview['missing'] as $student)
                                    <li>{{ $student->name }} <span class="text-zinc-400">· {{ $student->classRoom?->name ?? '-' }}</span></li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" form="dapodik-cancel" class="px-4 py-2 rounded-xl text-xs font-bold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">Batal</button>
                        <button type="submit" onclick="return confirm('Simpan data Dapodik ke data murid yang dicentang dan dipilih?')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                            <x-heroicon-o-check class="w-4 h-4" /> Simpan ke Data Murid
                        </button>
                    </div>
                </form>
                <form id="dapodik-cancel" method="POST" action="{{ route('students.dapodik.cancel') }}" class="hidden">@csrf</form>
            @endunless
        </div>
    </div>
</x-app-layout>
