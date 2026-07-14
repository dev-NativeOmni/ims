<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Program
            </h2>

            <div class="flex items-center gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <a
                        href="{{ route('programs.export') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Ekspor Excel
                    </a>

                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'import-programs')"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Impor Excel
                    </button>
                @endif

                <a
                    href="{{ route('programs.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Tambah Program
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">Frekuensi Pertemuan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Jumlah Kelas</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($programs as $program)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $program->name }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $program->description ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-600 capitalize">
                                        {{ $program->meeting_frequency ?? 'setiap hari' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $program->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $program->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $program->class_rooms_count }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('programs.show', $program) }}" class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('programs.edit', $program) }}" class="btn-action-edit">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('programs.destroy', $program) }}" onsubmit="return confirm('Hapus program ini?')">
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
                                        Belum ada data program.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $programs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
    <x-modal name="import-programs" :show="false" focusable>
        <form method="POST" action="{{ route('programs.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">
                Impor Data Program
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Unggah berkas Excel (.xlsx) atau CSV untuk mengimpor atau memperbarui data program secara massal.
            </p>

            <div class="mt-4 p-3 bg-gray-50 rounded text-xs text-gray-600 space-y-1 border border-gray-100">
                <p class="font-semibold text-gray-700">Format Kolom:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nama Program</strong> (Wajib): Nama unik program. Jika sudah ada, data akan diperbarui.</li>
                    <li><strong>Deskripsi</strong> (Opsional): Keterangan program.</li>
                    <li><strong>Frekuensi Pertemuan</strong> (Opsional): <code>setiap hari</code> / <code>seminggu sekali</code>. Default: setiap hari.</li>
                    <li><strong>Status</strong> (Opsional): <code>active</code> / <code>inactive</code>. Default: active.</li>
                </ul>
            </div>

            <div class="mt-6">
                <input
                    id="program_import_file"
                    name="file"
                    type="file"
                    accept=".xlsx,.csv,.txt"
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