<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center gap-2">
                    <span>🎯 Kelola Target Hafalan Santri</span>
                </h2>
                <p class="text-sm text-gray-600">
                    Pengisian target hafalan reguler per-santri dan target metode Ummi serentak per-Halaqah Musyrif.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('hafalan-targets.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    + Tambah Single Target
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $activeProgram = request('program', $activeProgram ?? 'reguler');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ═══════════════ PROGRAM MODE TOGGLE ═══════════════ --}}
            <div class="flex items-center gap-3 border-b border-gray-200 dark:border-zinc-800 pb-3">
                <a href="{{ route('hafalan-targets.index', array_merge(request()->except('page'), ['program' => 'reguler'])) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-extrabold text-sm transition {{ $activeProgram === 'reguler' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                    <span>📘 Program Reguler (Kelas 11 &amp; 12)</span>
                </a>

                <a href="{{ route('hafalan-targets.index', array_merge(request()->except('page'), ['program' => 'ummi'])) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-extrabold text-sm transition {{ $activeProgram === 'ummi' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                    <span>📗 Program Metode Ummi (Kelas 10 — Per-Halaqah Musyrif)</span>
                </a>
            </div>

            @if ($activeProgram === 'reguler')
                {{-- ═══════════════ PROGRAM REGULER SPREADSHEET INPUT ═══════════════ --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 space-y-5">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900 flex items-center gap-2">
                                <span>📘 Input Target Reguler Spreadsheet Per-Kelas</span>
                            </h3>
                            <p class="text-xs text-gray-500">Pilih kelas 11 atau 12 untuk mengisi Surah, Ayat, dan Tanggal Target seluruh santri di kelas tersebut sekaligus.</p>
                        </div>

                        <form method="GET" action="{{ route('hafalan-targets.index') }}" class="flex items-center gap-2">
                            <input type="hidden" name="program" value="reguler">
                            <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Pilih Kelas:</label>
                            <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}" @selected((string) request('class_room_id') === (string) $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if (request()->filled('class_room_id') && $students->isNotEmpty())
                        <form method="POST" action="{{ route('hafalan-targets.store-bulk-reguler') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="class_room_id" value="{{ request('class_room_id') }}">

                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-indigo-50/60">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 w-12">#</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 min-w-[200px]">Nama Santri</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 min-w-[200px]">Surah Target</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 w-24">Ayat Awal</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 w-24">Ayat Akhir</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 min-w-[160px]">Deadline Target</th>
                                            <th class="px-4 py-3 text-left font-bold text-indigo-900 min-w-[180px]">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($students as $idx => $st)
                                            <tr class="hover:bg-gray-50/60 transition">
                                                <td class="px-4 py-3 text-gray-500 font-semibold">{{ $idx + 1 }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-gray-900">{{ $st->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $st->student_number ?? '-' }}</div>
                                                    <input type="hidden" name="targets[{{ $idx }}][student_id]" value="{{ $st->id }}">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select name="targets[{{ $idx }}][surah_id]" class="w-full rounded-lg border-gray-300 text-xs font-semibold focus:ring-indigo-500">
                                                        <option value="">-- Pilih Surah --</option>
                                                        @foreach ($surahs as $surah)
                                                            <option value="{{ $surah->id }}">
                                                                {{ $surah->number }}. {{ $surah->name_latin }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" min="1" name="targets[{{ $idx }}][ayah_start]" placeholder="1" class="w-full rounded-lg border-gray-300 text-xs font-semibold text-center focus:ring-indigo-500">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" min="1" name="targets[{{ $idx }}][ayah_end]" placeholder="40" class="w-full rounded-lg border-gray-300 text-xs font-semibold text-center focus:ring-indigo-500">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="date" name="targets[{{ $idx }}][target_date]" value="{{ now()->addWeeks(2)->toDateString() }}" class="w-full rounded-lg border-gray-300 text-xs font-semibold focus:ring-indigo-500">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="text" name="targets[{{ $idx }}][notes]" placeholder="Catatan opsional..." class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition">
                                    <span>💾 Simpan Semua Target Reguler Kelas Ini</span>
                                </button>
                            </div>
                        </form>
                    @elseif (request()->filled('class_room_id'))
                        <div class="p-6 text-center text-sm text-gray-500">Tidak ada data santri di kelas ini.</div>
                    @else
                        <div class="p-8 text-center text-sm text-gray-500 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                            Silakan pilih kelas di atas untuk mulai mengisi target hafalan reguler santri.
                        </div>
                    @endif
                </div>

            @else
                {{-- ═══════════════ PROGRAM UMMI BULK TARGET PER HALAQAH ═══════════════ --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 space-y-5">
                    <div class="border-b border-gray-100 pb-4">
                        <h3 class="text-lg font-extrabold text-teal-900 flex items-center gap-2">
                            <span>📗 Target Metode Ummi Bulk Per-Halaqah Musyrif (Kelas 10)</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Tentukan Jilid, Halaman Peraga, Halaman Buku, Surah, dan Deadline secara serentak untuk seluruh santri anggota Halaqah Musyrif.</p>
                    </div>

                    <form method="POST" action="{{ route('hafalan-targets.store-bulk-ummi') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div class="lg:col-span-3 bg-teal-50/60 p-4 rounded-xl border border-teal-100">
                                <label class="block text-xs font-bold uppercase tracking-wider text-teal-800 mb-1.5">🕌 Pilih Halaqah Musyrif / Guru Pembimbing</label>
                                <select name="teacher_id" required class="w-full rounded-xl border-teal-300 text-sm font-bold text-teal-900 focus:ring-teal-500 focus:border-teal-500">
                                    @foreach ($teachers as $t)
                                        <option value="{{ $t->id }}" @selected((string) request('teacher_id', $currentTeacherId) === (string) $t->id)>
                                            Halaqah {{ $t->user?->name ?? 'Musyrif #'.$t->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">📖 Jilid Ummi</label>
                                <select name="ummi_jilid" required class="w-full rounded-xl border-gray-300 text-sm font-semibold focus:ring-teal-500">
                                    <option value="Jilid 1">Jilid 1</option>
                                    <option value="Jilid 2">Jilid 2</option>
                                    <option value="Jilid 3">Jilid 3</option>
                                    <option value="Jilid 4" selected>Jilid 4</option>
                                    <option value="Jilid 5">Jilid 5</option>
                                    <option value="Jilid 6">Jilid 6</option>
                                    <option value="Gharib">Gharib</option>
                                    <option value="Tajwid">Tajwid</option>
                                    <option value="Al-Qur'an">Al-Qur'an</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">🖼️ Halaman Peraga</label>
                                <input type="text" name="halaman_peraga" placeholder="Contoh: Hal. 10 - 15" class="w-full rounded-xl border-gray-300 text-sm font-semibold focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">📚 Halaman Buku</label>
                                <input type="text" name="halaman_buku" placeholder="Contoh: Hal. 15 - 20" class="w-full rounded-xl border-gray-300 text-sm font-semibold focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">🎯 Target Surah Hafalan Ummi</label>
                                <select name="surah_id" class="w-full rounded-xl border-gray-300 text-sm font-semibold focus:ring-teal-500">
                                    <option value="">-- Pilih Surah (Opsional) --</option>
                                    @foreach ($surahs as $surah)
                                        <option value="{{ $surah->id }}">
                                            {{ $surah->number }}. {{ $surah->name_latin }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">📅 Tanggal Deadline Target</label>
                                <input type="date" name="target_date" required value="{{ now()->addMonth()->toDateString() }}" class="w-full rounded-xl border-gray-300 text-sm font-semibold focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">📝 Catatan Pembimbing</label>
                                <input type="text" name="notes" placeholder="Catatan instruksi..." class="w-full rounded-xl border-gray-300 text-sm focus:ring-teal-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-500">
                                💡 Target Ummi ini akan otomatis diterapkan ke seluruh santri di bawah Halaqah Musyrif yang dipilih.
                            </p>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-teal-700 transition">
                                <span>🚀 Terapkan Target Ummi Ke Halaqah Ini</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- ═══════════════ KPI SUMMARY & TARGET LIST ═══════════════ --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Target</p>
                    <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ $summary['total'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Aktif</p>
                    <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ $summary['active'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Selesai</p>
                    <p class="mt-2 text-2xl font-extrabold text-emerald-600">{{ $summary['completed'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Terlewat</p>
                    <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ $summary['missed'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Lewat Deadline</p>
                    <p class="mt-2 text-2xl font-extrabold text-red-600">{{ $summary['overdue'] }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-extrabold text-base text-gray-900">Daftar Target Hafalan Tersimpan</h3>
                    <span class="text-xs font-semibold text-gray-500">Mode: {{ strtoupper($activeProgram) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Santri</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Musyrif / Guru</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Target Detail</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Deadline</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($targets as $target)
                                @php
                                    $statusClass = match ($target->status) {
                                        'active' => $target->is_overdue
                                            ? 'bg-red-50 text-red-700 border-red-200'
                                            : 'bg-blue-50 text-blue-700 border-blue-200',
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'missed' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'cancelled' => 'bg-gray-50 text-gray-700 border-gray-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp

                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-gray-900">{{ $target->student?->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">
                                            Kelas {{ $target->student?->classRoom?->name ?? '-' }} · NIS {{ $target->student?->student_number ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($target->ummi_jilid)
                                            <div class="font-bold text-teal-700">📗 {{ $target->ummi_jilid }}</div>
                                            <div class="text-xs text-gray-600">
                                                Peraga: {{ $target->halaman_peraga ?? '-' }} · Buku: {{ $target->halaman_buku ?? '-' }}
                                                @if($target->surah)
                                                    · Surah {{ $target->surah->name_latin }}
                                                @endif
                                            </div>
                                        @else
                                            <div class="font-bold text-indigo-700">📘 {{ $target->surah?->number }}. {{ $target->surah?->name_latin }}</div>
                                            <div class="text-xs text-gray-600">Ayat {{ $target->ayah_range }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">{{ $target->target_date?->format('d M Y') }}</div>
                                        @if ($target->is_overdue)
                                            <div class="text-xs font-bold text-red-600">Lewat deadline</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                            {{ $target->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($target->status !== 'completed')
                                                <form method="POST" action="{{ route('hafalan-targets.complete', $target) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-action-complete">
                                                        Selesai
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('hafalan-targets.destroy', $target) }}"
                                                  onsubmit="return confirm('Hapus target hafalan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-delete">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                        Belum ada target hafalan tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $targets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>