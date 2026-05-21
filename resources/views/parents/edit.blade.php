<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Orangtua/Wali
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('parents.update', $parent) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nama Orangtua/Wali
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $parent->user?->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Login
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email', $parent->user?->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password Baru
                            </label>
                            <input id="password" name="password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Kosongkan jika password tidak diganti.</p>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                Konfirmasi Password Baru
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Nomor Telepon
                            </label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $parent->phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status Akun
                            </label>
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="active" @selected(old('status', $parent->user?->status) === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status', $parent->user?->status) === 'inactive')>Nonaktif</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                Alamat
                            </label>
                            <textarea id="address" name="address" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('address', $parent->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('parents.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>