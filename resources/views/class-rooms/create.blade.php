<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Kelas
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('class-rooms.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="program_id" class="block text-sm font-medium text-gray-700">
                            Program
                        </label>
                        <select
                            id="program_id"
                            name="program_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                            <option value="">Pilih Program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) old('program_id', request('program_id')) === (string) $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Kelas
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">
                            Level
                        </label>
                        <input
                            id="level"
                            name="level"
                            type="text"
                            value="{{ old('level') }}"
                            placeholder="Contoh: Pemula, Menengah, Lanjutan"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                        @error('level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('class-rooms.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>