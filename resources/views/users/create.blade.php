<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Tambah User Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 flex justify-between items-center gap-4">
                <div>
                    <span class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block mb-1">Manajemen User</span>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Buat Akun Pengguna</h3>
                </div>
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-750 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                    Kembali
                </a>
            </div>

            <!-- Form Create User -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6">
                <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                    @csrf

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Nama Lengkap</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name') }}"
                            placeholder="Ketik nama lengkap pengguna"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            required
                        >
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            value="{{ old('username') }}"
                            placeholder="Ketik username untuk login"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            required
                        >
                        @error('username')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Peran (Role) -->
                    <div>
                        <label for="role_id" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Peran (Role)</label>
                        <select name="role_id" id="role_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white" required>
                            <option value="">-- Pilih Peran --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id') == $role->id) class="dark:bg-zinc-900">
                                    {{ $role->display_name }} ({{ $role->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Status Akun</label>
                        <select name="status" id="status" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white" required>
                            <option value="active" @selected(old('status', 'active') === 'active') class="dark:bg-zinc-900">Aktif</option>
                            <option value="inactive" @selected(old('status') === 'inactive') class="dark:bg-zinc-900">Nonaktif</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Password (Teks Biasa)</label>
                        <input 
                            type="text" 
                            name="password" 
                            id="password" 
                            value="{{ old('password') }}"
                            placeholder="Ketik password minimal 6 karakter"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            required
                        >
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-550 mt-1">Ketik password awal. Sistem akan mengenkripsi password ini untuk keamanan login serta menyimpannya dalam plaintext untuk kemudahan monitoring admin.</p>
                        @error('password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition duration-150 shadow-sm cursor-pointer">
                            Buat Akun Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
