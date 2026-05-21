<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Program
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('programs.update', $program) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Program
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $program->name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Deskripsi
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >{{ old('description', $program->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                            <option value="active" @selected(old('status', $program->status) === 'active')>Aktif</option>
                            <option value="inactive" @selected(old('status', $program->status) === 'inactive')>Nonaktif</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('programs.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>