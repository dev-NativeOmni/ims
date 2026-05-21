<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Kelas
            </h2>

            <a href="{{ route('class-rooms.edit', $classRoom) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Edit Kelas
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama Kelas</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Program</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->program?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Level</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->level ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Santri</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->students_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Santri di Kelas Ini
                </h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Nomor Santri</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $student->name }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->student_number ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ ucfirst($student->status) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada santri di kelas ini.
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
</x-app-layout>