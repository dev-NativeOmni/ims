<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Setoran Hafalan
            </h2>

            <a
                href="{{ route('hafalan-records.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Input Setoran
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('hafalan-records.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <select name="class_room_id" class="rounded-md border-gray-300 shadow-sm">
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
                        placeholder="Cari santri / surah"
                        class="rounded-md border-gray-300 shadow-sm"
                    >

                    <select name="surah_id" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Surah</option>
                        @foreach ($surahs as $surah)
                            <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                {{ $surah->number }}. {{ $surah->name_latin }}
                            </option>
                        @endforeach
                    </select>

                    <select name="submission_type" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Jenis</option>
                        <option value="new" @selected(request('submission_type') === 'new')>Baru</option>
                        <option value="continuation" @selected(request('submission_type') === 'continuation')>Lanjutan</option>
                        <option value="revision" @selected(request('submission_type') === 'revision')>Perbaikan</option>
                    </select>

                    <select name="status" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Status</option>
                        <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                        <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                        <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase hover:bg-gray-700">
                            Filter
                        </button>

                        <a href="{{ route('hafalan-records.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-200">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Santri</th>
                                <th class="px-4 py-3">Surah</th>
                                <th class="px-4 py-3">Ayat</th>
                                <th class="px-4 py-3">Jenis</th>
                                <th class="px-4 py-3">Nilai</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->submitted_at?->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $record->student?->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $record->student?->classRoom?->name ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->surah?->number }}. {{ $record->surah?->name_latin }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->submission_type_label }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->score !== null ? number_format((float) $record->score, 2) : '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold
                                            {{ $record->status === 'passed' ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $record->status === 'repeat' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $record->status === 'needs_improvement' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                        ">
                                            {{ $record->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
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
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada data setoran hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $hafalanRecords->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>