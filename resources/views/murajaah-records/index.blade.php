<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Data Murajaah
            </h2>

            <a href="{{ route('murajaah-records.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Tambah Murajaah
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('murajaah-records.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kelas</label>
                            <select name="class_room_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}" @selected(request('class_room_id') == $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cari</label>
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Nama santri / surah"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Surah</label>
                            <select name="surah_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Semua Surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected(request('surah_id') == $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                                <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                                <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                    class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold">
                                Filter
                            </button>

                            <a href="{{ route('murajaah-records.index') }}"
                               class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm font-semibold">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Santri</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Surah</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Ayat</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nilai</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Guru</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="px-4 py-3">
                                        {{ $record->reviewed_at?->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $record->student?->name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $record->student?->student_number ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $record->surah?->number }}. {{ $record->surah?->name_latin }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $record->ayah_range }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $record->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                         <div class="flex justify-end gap-2">
                                             <a href="{{ route('murajaah-records.show', $record) }}"
                                                class="btn-action-detail">
                                                 Detail
                                             </a>

                                             <a href="{{ route('murajaah-records.edit', $record) }}"
                                                class="btn-action-edit">
                                                 Edit
                                             </a>

                                             <form action="{{ route('murajaah-records.destroy', $record) }}"
                                                   method="POST"
                                                   onsubmit="return confirm('Yakin ingin menghapus data murajaah ini?')">
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
                                        Belum ada data murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $murajaahRecords->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>