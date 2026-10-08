<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center gap-2">
                    <x-heroicon-o-flag class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    <span>Target Bulanan</span>
                </h2>
                <p class="text-sm text-gray-600">
                    Pengisian target hafalan reguler per-murid dan target metode Ummi serentak per-Halaqah Musyrif.
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

            @include('hafalan-targets.partials.period-tabs')

            {{-- ═══════════════ PROGRAM MODE TOGGLE ═══════════════ --}}
            <div class="flex items-center gap-3 border-b border-gray-200 dark:border-zinc-800 pb-3">
                <a href="{{ route('hafalan-targets.index', array_merge(request()->except('page'), ['program' => 'reguler'])) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-extrabold text-sm transition {{ $activeProgram === 'reguler' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                    <x-heroicon-o-book-open class="w-4 h-4" />
                    <span>Program Reguler (Kelas 11 &amp; 12)</span>
                </a>

                <a href="{{ route('hafalan-targets.index', array_merge(request()->except('page'), ['program' => 'ummi'])) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-extrabold text-sm transition {{ $activeProgram === 'ummi' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                    <x-heroicon-o-bookmark class="w-4 h-4" />
                    <span>Program Metode Ummi (Kelas 10 — Per-Halaqah Musyrif)</span>
                </a>
            </div>

            @if ($activeProgram === 'reguler')
                {{-- Kelas 11 & 12: isian target per bulan, tabel yang sama dengan Target Triwulan (satu kolom bulan). --}}
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
                @include('hafalan-targets.partials.target-grid', ['grid' => $grid])

            @else
                {{-- Target Ummi diisi lewat tabel per murid (isi serentak + penyesuaian per murid). --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-teal-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-teal-900 flex items-center gap-2">
                            <x-heroicon-o-table-cells class="w-5 h-5 text-teal-600" />
                            <span>Isi Target Ummi Per Murid</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Target Jilid &amp; Halaman Buku serta Surah &amp; Ayat per bulan: isi serentak satu halaqah, lalu sesuaikan murid yang capaiannya berbeda.</p>
                    </div>
                    <a href="{{ route('hafalan-targets.ummi', request()->only(['teacher_id', 'class_room_id'])) }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-teal-700 transition">
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                        <span>Buka Target Ummi</span>
                    </a>
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

            <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100 space-y-4">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Musyrif / Guru Pengampu</p>
                    @php
                        $teacherUrl = function ($value) {
                            $params = request()->except(['teacher_id', 'page']);
                            if ($value !== '') {
                                $params['teacher_id'] = $value;
                            }

                            return request()->url().'?'.http_build_query($params);
                        };
                    @endphp
                    <select onchange="window.location.href=this.value" @disabled($teachers->count() <= 1 && $isTeacherOnly) class="w-full sm:w-64 rounded-lg border-zinc-300 bg-transparent text-xs font-semibold text-zinc-900 shadow-sm">
                        @if (! $isTeacherOnly)
                            <option value="{{ $teacherUrl('') }}" @selected(! request('teacher_id'))>Semua Musyrif / Guru</option>
                        @endif
                        @foreach ($teachers as $t)
                            <option value="{{ $teacherUrl($t->id) }}" @selected((string) request('teacher_id') === (string) $t->id)>
                                Halaqah {{ $t->user?->name ?? 'Musyrif #'.$t->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Kelas</p>
                    <x-filter-toggle
                        name="class_room_id"
                        :options="['' => 'Semua Kelas'] + $classRooms->pluck('name', 'id')->all()"
                    />
                </div>

                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Bulan (Deadline)</p>
                    @php
                        $monthUrl = function ($value) {
                            $params = request()->except(['month', 'page']);
                            if ($value !== '') {
                                $params['month'] = $value;
                            }

                            return request()->url().'?'.http_build_query($params);
                        };
                    @endphp
                    <select onchange="window.location.href=this.value" class="w-full sm:w-64 rounded-lg border-zinc-300 bg-transparent text-xs font-semibold text-zinc-900 shadow-sm">
                        <option value="{{ $monthUrl('') }}" @selected(! request('month'))>Semua Bulan</option>
                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $monthUrl($value) }}" @selected(request('month') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Status</p>
                    <x-filter-toggle
                        name="status"
                        :options="['' => 'Semua', 'active' => 'Aktif', 'completed' => 'Selesai', 'missed' => 'Terlewat', 'cancelled' => 'Dibatalkan']"
                        :colors="['active' => 'bg-blue-600 text-white shadow-sm', 'completed' => 'bg-emerald-600 text-white shadow-sm', 'missed' => 'bg-amber-600 text-white shadow-sm', 'cancelled' => 'bg-zinc-500 text-white shadow-sm']"
                    />
                </div>
            </div>

            <div x-data="{
                selectedTargets: [],
                allIds: {{ json_encode($targets->pluck('id')->all()) }},
                selectAll: false,
                toggleAll() {
                    if (this.selectAll) {
                        this.selectedTargets = [...this.allIds];
                    } else {
                        this.selectedTargets = [];
                    }
                },
                updateSelectAll() {
                    this.selectAll = this.allIds.length > 0 && this.selectedTargets.length === this.allIds.length;
                }
            }" class="overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-200">

                {{-- Floating / Top Bulk Action Bar --}}
                <div x-show="selectedTargets.length > 0"
                     x-transition
                     class="bg-emerald-800 text-white px-6 py-3 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-20 shadow-md">
                    <div class="flex items-center gap-2">
                        <span class="bg-white text-emerald-900 text-xs font-black px-2.5 py-1 rounded-lg shadow-sm">
                            <span x-text="selectedTargets.length"></span> Terpilih
                        </span>
                        <span class="text-sm font-bold">Pilih Aksi Massal untuk target yang dicentang:</span>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Form Bulk Complete --}}
                        <form method="POST" action="{{ route('hafalan-targets.bulk-complete') }}" class="inline">
                            @csrf
                            <template x-for="id in selectedTargets" :key="'comp_' + id">
                                <input type="hidden" name="target_ids[]" :value="id">
                            </template>
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 px-3.5 py-1.5 text-xs font-bold text-white shadow transition cursor-pointer">
                                <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                <span>Tandai Selesai (<span x-text="selectedTargets.length"></span>)</span>
                            </button>
                        </form>

                        {{-- Form Bulk Destroy --}}
                        <form method="POST" action="{{ route('hafalan-targets.bulk-destroy') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target yang dicentang?')" class="inline">
                            @csrf
                            <template x-for="id in selectedTargets" :key="'del_' + id">
                                <input type="hidden" name="target_ids[]" :value="id">
                            </template>
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 px-3.5 py-1.5 text-xs font-bold text-white shadow transition cursor-pointer">
                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                <span>Hapus Terpilih (<span x-text="selectedTargets.length"></span>)</span>
                            </button>
                        </form>

                        <button type="button" @click="selectedTargets = []; selectAll = false" class="text-xs text-emerald-200 hover:text-white underline ml-2 cursor-pointer">
                            Batal
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="font-extrabold text-base text-gray-900">Daftar Target Hafalan Tersimpan</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Centang target pada tabel untuk menyelesaikan atau menghapus beberapa target sekaligus.</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">Mode: {{ strtoupper($activeProgram) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center w-10">
                                    <input type="checkbox"
                                           x-model="selectAll"
                                           @change="toggleAll()"
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                </th>
                                <th class="px-4 py-3 text-left font-bold text-gray-600">Murid</th>
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

                                <tr :class="selectedTargets.includes({{ $target->id }}) ? 'bg-emerald-50/60' : ''" class="hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox"
                                               :value="{{ $target->id }}"
                                               x-model="selectedTargets"
                                               @change="updateSelectAll()"
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="font-bold text-gray-900">{{ $target->student?->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">
                                            Kelas {{ $target->student?->classRoom?->name ?? '-' }} · NIS/NISN {{ $target->student?->nisNisn() ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($target->ummi_jilid)
                                            <div class="font-bold text-teal-700 flex items-center gap-1">
                                                <x-heroicon-o-bookmark class="w-3.5 h-3.5 text-teal-600" />
                                                <span>{{ $target->ummi_jilid }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                Buku hal. {{ \App\Support\AyahLabel::end($target->halaman_buku) }}
                                                @if($target->surah)
                                                    · Surah {{ $target->surah->name_latin }}{{ $target->ayah ? ' ayat '.$target->ayah : '' }}
                                                @endif
                                            </div>
                                            @if ($target->book_status || $target->surah_status)
                                                <div class="mt-1 flex flex-wrap gap-1 text-[10px] font-bold">
                                                    @foreach (['Buku' => $target->book_status, 'Hafal' => $target->surah_status] as $partLabel => $partStatus)
                                                        @continue(! $partStatus)
                                                        <span class="px-1.5 py-0.5 rounded {{ ['completed' => 'bg-emerald-100 text-emerald-700', 'missed' => 'bg-rose-100 text-rose-700'][$partStatus] ?? 'bg-sky-100 text-sky-700' }}">
                                                            {{ $partLabel }}: {{ ['completed' => 'Selesai', 'missed' => 'Terlewat'][$partStatus] ?? 'Aktif' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            <div class="font-bold text-indigo-700 flex items-center gap-1">
                                                <x-heroicon-o-book-open class="w-3.5 h-3.5 text-indigo-600" />
                                                <span>{{ $target->surah?->number }}. {{ $target->surah?->name_latin }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600">Ayat {{ $target->ayah_range }}</div>
                                            @if ($target->is_auto)
                                                <span class="mt-1 inline-flex items-center rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700 border border-sky-200" title="Target lama dari perhitungan otomatis (sudah tidak dipakai). Edit atau isi ulang di Target Triwulan.">Otomatis lama</span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">{{ $target->target_date?->format('d M Y') }}</div>
                                        @if ($target->deadline_manual)
                                            <span class="inline-block rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700" title="Deadline diatur manual, tidak ikut penyesuaian otomatis">Manual</span>
                                        @endif
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
                                            @can('update', $target)
                                                <a href="{{ route('hafalan-targets.edit', ['hafalan_target' => $target, 'back' => request()->fullUrl()]) }}"
                                                   class="btn-action-edit">
                                                    Edit
                                                </a>
                                            @endcan
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
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-500">
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