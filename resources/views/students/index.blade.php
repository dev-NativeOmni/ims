<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Santri
            </h2>

            <a
                href="{{ route('students.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Tambah Santri
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
                <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama / nomor santri"
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
                                <th class="px-4 py-3">Santri</th>
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
                                            <a href="{{ route('students.show', $student) }}" class="text-sm text-blue-600 hover:underline">
                                                Detail
                                            </a>

                                            <a href="{{ route('students.edit', $student) }}" class="text-sm text-yellow-600 hover:underline">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus santri ini? Data akan soft delete.')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="text-sm text-red-600 hover:underline">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada data santri.
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
</x-app-layout>