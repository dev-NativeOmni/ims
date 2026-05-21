<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Santri
            </h2>

            <a
                href="{{ route('students.edit', $student) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Edit Santri
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Identitas Santri
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama</p>
                        <p class="font-semibold text-gray-900">{{ $student->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Nomor Santri / NIS</p>
                        <p class="font-semibold text-gray-900">{{ $student->student_number ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Gender</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Tanggal Lahir</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->birth_date?->format('d M Y') ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-gray-900">
                            @if ($student->status === 'active')
                                Aktif
                            @elseif ($student->status === 'inactive')
                                Nonaktif
                            @else
                                Lulus
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Akun Login</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->user?->email ?: 'Belum dihubungkan' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Akademik
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Program</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->classRoom?->program?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Kelas</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->classRoom?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->teacher?->user?->name ?: '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Orangtua/Wali
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Relasi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->parents as $parent)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $parent->user?->name }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->user?->email ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->phone ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->pivot?->relation ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada orangtua/wali terhubung.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar santri
                </a>
            </div>
        </div>
    </div>
</x-app-layout>