<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Program
            </h2>

            <a href="{{ route('class-rooms.create', ['program_id' => $program->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Tambah Kelas
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama Program</p>
                        <p class="font-semibold text-gray-900">{{ $program->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Frekuensi Pertemuan</p>
                        <p class="font-semibold text-gray-900 capitalize">{{ $program->meeting_frequency ?? 'setiap hari' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-gray-900">
                            {{ $program->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Kelas</p>
                        <p class="font-semibold text-gray-900">{{ $program->class_rooms_count }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-gray-500">Deskripsi</p>
                    <p class="text-gray-700">{{ $program->description ?: '-' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-900 mb-4">Kelas dalam Program Ini</h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                            <th class="px-4 py-3">Nama Kelas</th>
                            <th class="px-4 py-3">Level</th>
                            <th class="px-4 py-3">Jumlah Santri</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($classRooms as $classRoom)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $classRoom->name }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $classRoom->level ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $classRoom->students_count }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('class-rooms.show', $classRoom) }}" class="text-sm text-blue-600 hover:underline">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada kelas di program ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $classRooms->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>