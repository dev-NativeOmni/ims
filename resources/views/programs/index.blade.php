<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Program
            </h2>

            <a
                href="{{ route('programs.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Tambah Program
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Deskripsi</th>
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
                                            <a href="{{ route('programs.show', $program) }}" class="text-sm text-blue-600 hover:underline">
                                                Detail
                                            </a>

                                            <a href="{{ route('programs.edit', $program) }}" class="text-sm text-yellow-600 hover:underline">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('programs.destroy', $program) }}" onsubmit="return confirm('Hapus program ini?')">
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
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
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
</x-app-layout>