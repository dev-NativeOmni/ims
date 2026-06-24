<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Orangtua/Wali
            </h2>

            <a
                href="{{ route('parents.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Tambah Orangtua
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
                <form method="GET" action="{{ route('parents.index') }}" class="flex flex-col md:flex-row gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama, username, telepon, alamat"
                        class="rounded-md border-gray-300 shadow-sm md:w-96"
                    >

                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase hover:bg-gray-700">
                        Cari
                    </button>

                    <a href="{{ route('parents.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-200">
                        Reset
                    </a>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Orangtua/Wali</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Alamat</th>
                                <th class="px-4 py-3">Jumlah Santri</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $parent->user?->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $parent->user?->username }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->phone ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->address ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->students_count }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $parent->user?->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $parent->user?->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('parents.show', $parent) }}" class="text-sm text-blue-600 hover:underline">
                                                Detail
                                            </a>

                                            <a href="{{ route('parents.edit', $parent) }}" class="text-sm text-yellow-600 hover:underline">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('parents.destroy', $parent) }}" onsubmit="return confirm('Hapus orangtua/wali ini?')">
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
                                        Belum ada data orangtua/wali.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $parents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>