<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Orangtua/Wali
            </h2>

            <a href="{{ route('parents.edit', $parent) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Edit Orangtua
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Identitas Orangtua/Wali
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama</p>
                        <p class="font-semibold text-gray-900">{{ $parent->user?->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-semibold text-gray-900">{{ $parent->user?->email }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Telepon</p>
                        <p class="font-semibold text-gray-900">{{ $parent->phone ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status Akun</p>
                        <p class="font-semibold text-gray-900">
                            {{ $parent->user?->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Santri Terhubung</p>
                        <p class="font-semibold text-gray-900">{{ $parent->students_count }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-gray-500">Alamat</p>
                    <p class="text-gray-700">{{ $parent->address ?: '-' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Santri Terhubung
                </h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                            <th class="px-4 py-3">Santri</th>
                            <th class="px-4 py-3">Relasi</th>
                            <th class="px-4 py-3">Kelas</th>
                            <th class="px-4 py-3">Guru</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($parent->students as $student)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">
                                        {{ $student->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $student->student_number ?: '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->pivot?->relation ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->classRoom?->name ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->teacher?->user?->name ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->status === 'active' ? 'Aktif' : ($student->status === 'inactive' ? 'Nonaktif' : 'Lulus') }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('students.show', $student) }}" class="text-sm text-blue-600 hover:underline">
                                        Detail Santri
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada santri terhubung.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('parents.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar orangtua
                </a>
            </div>
        </div>
    </div>
</x-app-layout>