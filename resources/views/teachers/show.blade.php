<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Guru/Musyrif
            </h2>

            <a href="{{ route('teachers.edit', $teacher) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Edit Guru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Identitas Guru
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama</p>
                        <p class="font-semibold text-gray-900">{{ $teacher->user?->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Username</p>
                        <p class="font-semibold text-gray-900">{{ $teacher->user?->username }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Nomor Pegawai</p>
                        <p class="font-semibold text-gray-900">{{ $teacher->employee_number ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Telepon</p>
                        <p class="font-semibold text-gray-900">{{ $teacher->phone ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status Akun</p>
                        <p class="font-semibold text-gray-900">
                            {{ $teacher->user?->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Murid Bimbingan</p>
                        <p class="font-semibold text-gray-900">{{ $teacher->students_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Murid Bimbingan
                </h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                            <th class="px-4 py-3">Murid</th>
                            <th class="px-4 py-3">Kelas</th>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teacher->students as $student)
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
                                    {{ $student->classRoom?->name ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->classRoom?->program?->name ?: '-' }}
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

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('students.show', $student) }}" class="text-sm text-gray-600 hover:underline">
                                        Detail Murid
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada murid bimbingan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('teachers.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar guru
                </a>
            </div>
        </div>
    </div>
</x-app-layout>