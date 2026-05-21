<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Guru/Musyrif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('teachers.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nama Guru
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Login
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <input id="password" name="password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                Konfirmasi Password
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label for="employee_number" class="block text-sm font-medium text-gray-700">
                                Nomor Pegawai
                            </label>
                            <input id="employee_number" name="employee_number" type="text" value="{{ old('employee_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('employee_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Nomor Telepon
                            </label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status Akun
                            </label>
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('teachers.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>