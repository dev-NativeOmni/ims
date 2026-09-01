<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Setoran Hafalan
            </h2>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('spreadsheet-input.index') }}"
                    class="inline-flex items-center justify-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150 shrink-0 min-h-[38px]"
                >
                    📊 Input Spreadsheet
                </a>
                <a
                    href="{{ route('hafalan-records.create') }}"
                    class="inline-flex items-center justify-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150 shrink-0 min-h-[38px]"
                >
                    + Input Per Murid
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-3 sm:py-6" x-data="{
        selectedIds: [],
        selectAll: false,
        category: '{{ request('category', 'reguler') }}',
        toggleSelectAll(recordIds) {
            if (this.selectAll) {
                this.selectedIds = recordIds.map(id => String(id));
            } else {
                this.selectedIds = [];
            }
        }
    }">
        <div class="max-w-7xl mx-auto space-y-3 sm:space-y-4">
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl text-sm font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Bulk Action Bar -->
            <div x-show="selectedIds.length > 0" x-transition class="flex items-center justify-between bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 p-3 rounded-xl shadow-sm">
                <span class="text-xs font-bold text-rose-800 dark:text-rose-300">
                    <span x-text="selectedIds.length"></span> data dipilih
                </span>
                <form method="POST" :action="category === 'ummi' ? '{{ route('ummi-records.bulk-destroy') }}' : '{{ route('hafalan-records.bulk-destroy') }}'" onsubmit="return confirm('Hapus semua data yang dipilih?')" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs shadow transition cursor-pointer flex items-center gap-1.5">
                        <x-heroicon-o-trash class="w-4 h-4" /> Hapus Terpilih (<span x-text="selectedIds.length"></span>)
                    </button>
                </form>
            </div>

            <!-- Hafalan Category Tabs (Scrollable on Mobile) -->
            <div class="flex overflow-x-auto items-center gap-2 sm:gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-2 sm:pb-3 scrollbar-none">
                <a href="{{ route('hafalan-records.index', array_merge(request()->except('class_room_id', 'page'), ['category' => 'reguler'])) }}"
                   class="px-3.5 py-2 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition min-h-[36px] inline-flex items-center {{ request('category', 'reguler') !== 'ummi' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                    📖 Hafalan Reguler (Juz 1–30)
                </a>
                <a href="{{ route('hafalan-records.index', array_merge(request()->except('class_room_id', 'page'), ['category' => 'ummi'])) }}"
                   class="px-3.5 py-2 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition min-h-[36px] inline-flex items-center {{ request('category') === 'ummi' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                    🌱 Hafalan Metode Ummi <span class="text-[10px] uppercase font-black px-1.5 py-0.5 rounded bg-amber-400 text-black ml-1">Khusus Mulai Kelas 10</span>
                </a>
            </div>

            <!-- Quick Class Filter Buttons (Tombol Filter Kelas) -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-3 sm:p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                        🏫 Filter Kelas Fast-Access:
                    </span>
                    @if(request('class_room_id'))
                        <a href="{{ route('hafalan-records.index', request()->except('class_room_id', 'page')) }}" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            ✕ Hapus Filter Kelas
                        </a>
                    @endif
                </div>

                <div class="flex overflow-x-auto items-center gap-1.5 sm:gap-2 pb-1 scrollbar-none">
                    <a href="{{ route('hafalan-records.index', request()->except('class_room_id', 'page')) }}"
                       class="px-3.5 py-2 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition cursor-pointer {{ !request('class_room_id') ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-md' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                        Semua Kelas
                    </a>
                    @foreach ($classRooms as $class)
                        @php
                            $isSelected = (string) request('class_room_id') === (string) $class->id;
                        @endphp
                        <a href="{{ route('hafalan-records.index', array_merge(request()->except('page'), ['class_room_id' => $class->id])) }}"
                           class="px-3.5 py-2 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition cursor-pointer {{ $isSelected ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-400' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                            {{ $class->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-3.5 sm:p-5">
                <form method="GET" action="{{ route('hafalan-records.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 {{ request('category') === 'ummi' ? 'lg:grid-cols-4' : 'lg:grid-cols-6' }} gap-2.5 sm:gap-3">
                    <input type="hidden" name="category" value="{{ request('category', 'reguler') }}">

                    <select name="class_room_id" onchange="this.form.submit()" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($classRooms as $class)
                            <option value="{{ $class->id }}" @selected((string) request('class_room_id') === (string) $class->id)>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari murid / surah..."
                        class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 shadow-sm"
                    >

                    <input
                        type="date"
                        name="date"
                        value="{{ request('date') }}"
                        class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm"
                    >

                    <select name="surah_id" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm">
                        <option value="">Semua Surah</option>
                        @foreach ($surahs as $surah)
                            <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                {{ $surah->number }}. {{ $surah->name_latin }}
                            </option>
                        @endforeach
                    </select>

                    @if (request('category') !== 'ummi')
                    <select name="submission_type" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm">
                        <option value="">Semua Jenis</option>
                        <option value="new" @selected(request('submission_type') === 'new')>Baru</option>
                        <option value="continuation" @selected(request('submission_type') === 'continuation')>Lanjutan</option>
                        <option value="revision" @selected(request('submission_type') === 'revision')>Perbaikan</option>
                    </select>

                    <select name="status" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm">
                        <option value="">Semua Status</option>
                        <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                        <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                        <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                    </select>
                    @endif

                    <div class="flex gap-2 col-span-1 sm:col-span-2 md:col-span-1">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 rounded-lg text-xs font-bold text-white uppercase tracking-wider transition min-h-[38px]">
                            Filter
                        </button>

                        <a href="{{ route('hafalan-records.index', ['category' => request('category', 'reguler')]) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 active:scale-95 rounded-lg text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider transition min-h-[38px]">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Mobile View: Card Stack (< md) -->
            <div class="block md:hidden space-y-3">
                @if (request('category') === 'ummi')
                    @forelse ($hafalanRecords as $record)
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                                <div>
                                    <h3 class="font-bold text-sm text-zinc-900 dark:text-white leading-tight">
                                        {{ $record->student?->name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 flex-wrap text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        <span>{{ $record->student?->classRoom?->name ?: '-' }}</span>
                                        <span>•</span>
                                        <span>{{ $record->tanggal?->format('d M Y') }}</span>
                                        @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                            <span>•</span>
                                            <a href="{{ route('hafalan-records.student.ummi-card', $record->student_id) }}" 
                                               target="_blank"
                                               class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-0.5">
                                                📄 Kartu
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold shrink-0 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50">
                                    TM-{{ $record->tatap_muka }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Jilid / Hal</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ $record->ummi_jilid ?: '-' }} {{ $record->ummi_halaman ? 'Hal. ' . $record->ummi_halaman : '' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Materi & Nilai</span>
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ $record->materi ?: '-' }} | <strong class="text-indigo-600 dark:text-indigo-400">{{ $record->nilai ?? '-' }}</strong>
                                    </span>
                                </div>
                                @if($record->surah)
                                <div class="col-span-2 mt-1">
                                    <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Hafalan UMMI</span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $record->surah?->number }}. {{ $record->surah?->name_latin }} ({{ $record->hafalan_ayah ?: '-' }}) ({{ $record->lines_count }} Baris)
                                    </span>
                                </div>
                                @endif
                            </div>
                            @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                <div class="flex items-center gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                    <a href="{{ route('ummi-records.edit', $record) }}" class="btn-action-edit flex-1 text-center">
                                        ✏️ Edit
                                    </a>
                                    <form method="POST" action="{{ route('ummi-records.destroy', $record) }}" onsubmit="return confirm('Hapus data progres UMMI ini?')" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-delete w-full">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 text-center text-xs text-zinc-500">
                            Belum ada data catatan Tahsin UMMI.
                        </div>
                    @endforelse
                @else
                    @forelse ($hafalanRecords as $record)
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                                <div>
                                    <h3 class="font-bold text-sm text-zinc-900 dark:text-white leading-tight">
                                        {{ $record->student?->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        <span>{{ $record->student?->classRoom?->name ?: '-' }}</span>
                                        <span>•</span>
                                        <span>{{ $record->submitted_at?->format('d M Y') }}</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold shrink-0
                                    {{ $record->status === 'passed' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : '' }}
                                    {{ $record->status === 'repeat' ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400' : '' }}
                                    {{ $record->status === 'needs_improvement' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : '' }}
                                ">
                                    {{ $record->status_label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Surah & Ayat</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ $record->surah?->number }}. {{ $record->surah?->name_latin }} ({{ $record->ayah_start }}-{{ $record->ayah_end }}) ({{ $record->lines_count }} Baris)
                                    </span>
                                </div>
                                <div>
                                    <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Jenis & Nilai</span>
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ $record->submission_type_label }} | <strong class="text-indigo-600 dark:text-indigo-400">{{ $record->score_letter ?? '-' }}</strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Action Bar Mobile -->
                            <div class="flex items-center gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                <a href="{{ route('hafalan-records.show', $record) }}" class="btn-action-detail flex-1">
                                    🔍 Detail
                                </a>
                                <a href="{{ route('hafalan-records.edit', $record) }}" class="btn-action-edit flex-1">
                                    ✏️ Edit
                                </a>
                                <form method="POST" action="{{ route('hafalan-records.destroy', $record) }}" onsubmit="return confirm('Hapus setoran hafalan ini? Data akan soft delete.')" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action-delete w-full">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 text-center text-xs text-zinc-500">
                            Belum ada data setoran hafalan.
                        </div>
                    @endforelse
                @endif

                <div class="mt-4">
                    {{ $hafalanRecords->links() }}
                </div>
            </div>

            <!-- Desktop View: Table (>= md) -->
            <div class="hidden md:block bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm rounded-xl">
                <div class="p-4 sm:p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        @if (request('category') === 'ummi')
                            <thead>
                                <tr class="text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                        <th class="px-3 py-3 text-center">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll([{{ $hafalanRecords->pluck('id')->implode(',') }}])" class="rounded border-zinc-300 dark:border-zinc-700 text-rose-600 focus:ring-rose-500">
                                        </th>
                                    @endif
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Murid</th>
                                    <th class="px-4 py-3">Tatap Muka</th>
                                    <th class="px-4 py-3">Jilid / Hal</th>
                                    <th class="px-4 py-3">Materi</th>
                                    <th class="px-4 py-3">Hafalan UMMI</th>
                                    <th class="px-4 py-3 text-center">Baris</th>
                                    <th class="px-4 py-3">Nilai</th>
                                    <th class="px-4 py-3">Simak</th>
                                    @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                @forelse ($hafalanRecords as $record)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition duration-150">
                                        @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                            <td class="px-3 py-3.5 text-center">
                                                <input type="checkbox" value="{{ $record->id }}" x-model="selectedIds" class="rounded border-zinc-300 dark:border-zinc-700 text-rose-600 focus:ring-rose-500">
                                            </td>
                                        @endif
                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            {{ $record->tanggal?->format('d M Y') }}
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <div class="font-bold text-xs text-zinc-900 dark:text-white">
                                                {{ $record->student?->name }}
                                            </div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mt-0.5">
                                                <span>{{ $record->student?->classRoom?->name ?: '-' }}</span>
                                                @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                                    <span>•</span>
                                                    <a href="{{ route('hafalan-records.student.ummi-card', $record->student_id) }}" 
                                                       target="_blank"
                                                       class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-0.5">
                                                        📄 Kartu
                                                    </a>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            TM-{{ $record->tatap_muka }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 font-semibold whitespace-nowrap">
                                            {{ $record->ummi_jilid ?: '-' }} {{ $record->ummi_halaman ? 'Hal. ' . $record->ummi_halaman : '' }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                            {{ $record->materi ?: '-' }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            @if($record->surah)
                                                {{ $record->surah?->number }}. {{ $record->surah?->name_latin }} ({{ $record->hafalan_ayah ?: '-' }})
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 text-center font-bold">
                                            {{ $record->lines_count }}
                                        </td>

                                        <td class="px-4 py-3.5 font-bold text-xs text-zinc-800 dark:text-zinc-200">
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900 font-bold">
                                                {{ $record->nilai ?: '-' }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3.5 text-[11px] text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                            Guru: <span class="font-semibold {{ $record->disimak_guru === 'Ya' ? 'text-emerald-600' : 'text-zinc-400' }}">{{ $record->disimak_guru }}</span> | 
                                            Ortu: <span class="font-semibold {{ $record->disimak_ortu === 'Ya' ? 'text-emerald-600' : 'text-zinc-400' }}">{{ $record->disimak_ortu }}</span>
                                        </td>

                                        @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <a href="{{ route('ummi-records.edit', $record) }}" class="btn-action-edit">
                                                        ✏️ Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('ummi-records.destroy', $record) }}" onsubmit="return confirm('Hapus data progres UMMI ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action-delete">
                                                            🗑️ Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-6 text-center text-xs text-zinc-500">
                                            Belum ada data catatan Tahsin UMMI.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @else
                            <thead>
                                <tr class="text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                        <th class="px-3 py-3 text-center">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll([{{ $hafalanRecords->pluck('id')->implode(',') }}])" class="rounded border-zinc-300 dark:border-zinc-700 text-rose-600 focus:ring-rose-500">
                                        </th>
                                    @endif
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Murid</th>
                                    <th class="px-4 py-3">Surah</th>
                                    <th class="px-4 py-3">Ayat</th>
                                    <th class="px-4 py-3 text-center">Baris</th>
                                    <th class="px-4 py-3">Jenis</th>
                                    <th class="px-4 py-3">Nilai</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                @forelse ($hafalanRecords as $record)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition duration-150">
                                        @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                            <td class="px-3 py-3.5 text-center">
                                                <input type="checkbox" value="{{ $record->id }}" x-model="selectedIds" class="rounded border-zinc-300 dark:border-zinc-700 text-rose-600 focus:ring-rose-500">
                                            </td>
                                        @endif
                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            {{ $record->submitted_at?->format('d M Y') }}
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <div class="font-bold text-xs text-zinc-900 dark:text-white">
                                                {{ $record->student?->name }}
                                            </div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                {{ $record->student?->classRoom?->name ?: '-' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                            {{ $record->surah?->number }}. {{ $record->surah?->name_latin }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 text-center font-bold">
                                            {{ $record->lines_count }}
                                        </td>

                                        <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                            {{ $record->submission_type_label }}
                                        </td>

                                        <td class="px-4 py-3.5 font-bold text-xs text-zinc-800 dark:text-zinc-200">
                                            {{ $record->score_letter ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap inline-block
                                                {{ $record->status === 'passed' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : '' }}
                                                {{ $record->status === 'repeat' ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400' : '' }}
                                                {{ $record->status === 'needs_improvement' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : '' }}
                                            ">
                                                {{ $record->status_label }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <div class="flex justify-end items-center gap-2">
                                                <a href="{{ route('hafalan-records.show', $record) }}" class="btn-action-detail">
                                                    Detail
                                                </a>

                                                <a href="{{ route('hafalan-records.edit', $record) }}" class="btn-action-edit">
                                                    Edit
                                                </a>

                                                <form method="POST" action="{{ route('hafalan-records.destroy', $record) }}" onsubmit="return confirm('Hapus setoran hafalan ini? Data akan soft delete.')">
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
                                        <td colspan="8" class="px-4 py-6 text-center text-xs text-zinc-500">
                                            Belum ada data setoran hafalan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @endif
                    </table>

                    <div class="mt-4">
                        {{ $hafalanRecords->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>