<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Murid
            </h2>

            <div class="flex items-center gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <a
                        href="{{ route('students.export') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Ekspor Excel
                    </a>

                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'import-students')"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Impor Excel
                    </button>
                @endif

                <a
                    href="{{ route('students.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Tambah Murid
                </a>
            </div>
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
                <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama / nomor murid"
                        class="rounded-md border-gray-300 shadow-sm"
                    >

                    <select name="class_room_id" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($classRooms as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        <option value="graduated" @selected(request('status') === 'graduated')>Lulus</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase hover:bg-gray-700">
                            Filter
                        </button>

                        <a href="{{ route('students.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-200">
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
                                <th class="px-4 py-3">Murid</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Guru</th>
                                <th class="px-4 py-3">Orangtua/Wali</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $student->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $student->student_number ?: 'Nomor belum diisi' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $student->classRoom?->name ?: '-' }}
                                        @if ($student->classRoom?->program)
                                            <div class="text-sm text-gray-500">
                                                {{ $student->classRoom->program->name }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $student->teacher?->user?->name ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @forelse ($student->parents as $parent)
                                            <div>
                                                {{ $parent->user?->name }}
                                                @if ($parent->pivot?->relation)
                                                    <span class="text-xs text-gray-500">
                                                        ({{ $parent->pivot->relation }})
                                                    </span>
                                                @endif
                                            </div>
                                        @empty
                                            -
                                        @endforelse
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold
                                            {{ $student->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $student->status === 'inactive' ? 'bg-gray-100 text-gray-700' : '' }}
                                            {{ $student->status === 'graduated' ? 'bg-blue-100 text-blue-700' : '' }}
                                        ">
                                            @if ($student->status === 'active')
                                                Aktif
                                            @elseif ($student->status === 'inactive')
                                                Nonaktif
                                            @else
                                                Lulus
                                            @endif
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('students.show', $student) }}" class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('students.edit', $student) }}" class="btn-action-edit">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus murid ini? Data akan soft delete.')">
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
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada data murid.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
    <x-modal name="import-students" :show="false" focusable>
        <form method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">
                Impor Data Murid
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Unggah berkas Excel (.xlsx / .xls) untuk mengimpor atau memperbarui data murid secara massal.
            </p>

            <div class="mt-4 p-3 bg-gray-50 rounded text-xs text-gray-600 space-y-1 border border-gray-100 max-h-60 overflow-y-auto">
                <p class="font-semibold text-gray-700">Format Kolom Berkas Excel:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nama Murid</strong> (Wajib): Nama lengkap murid</li>
                    <li><strong>Nomor Induk</strong> (Opsional, kunci pencocokan): Jika diisi dan sudah ada, data murid akan diperbarui. Jika belum ada, data murid baru akan dibuat.</li>
                    <li><strong>Jenis Kelamin</strong> (Opsional): male / female</li>
                    <li><strong>Tanggal Lahir</strong> (Opsional): format YYYY-MM-DD</li>
                    <li><strong>Status</strong> (Opsional): active / inactive / graduated</li>
                    <li><strong>Kelas</strong> (Opsional): Nama kelas yang sesuai di sistem</li>
                    <li><strong>Level Tahfizh</strong> (Opsional): <code>tahsin</code>, <code>reguler</code>, <code>akselerasi</code>, atau <code>ummi</code></li>
                    <li><strong>Username Guru</strong> (Opsional): Username akun guru pembimbing</li>
                    <li><strong>Username Murid</strong> (Opsional): Username akun murid</li>
                    <li><strong>Username Orangtua</strong> (Opsional): Username akun orangtua, pisahkan dengan koma jika memiliki lebih dari satu orangtua (contoh: <code>ortu1,ortu2</code>).</li>
                    <li><strong>Hubungan Orangtua</strong> (Opsional): Relasi orangtua, pisahkan dengan koma (contoh: <code>Ayah,Ibu</code>). Harus berurutan sesuai dengan Username Orangtua.</li>
                </ul>
            </div>

            <div class="mt-6">
                <x-input-label for="excel_file" value="Pilih Berkas Excel" class="sr-only" />

                <input
                    id="excel_file"
                    name="file"
                    type="file"
                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    class="block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    required
                />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button class="ms-3 bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-800">
                    Proses Impor
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    @endif
</x-app-layout>