<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Edit Kredensial User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 flex justify-between items-center gap-4">
                <div>
                    <span class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block mb-1">Mengedit Kredensial</span>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $user->username }}</h3>
                </div>
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-750 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                    Kembali
                </a>
            </div>

            <!-- Form Edit User -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6">
                <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Nama Lengkap</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $user->name) }}"
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
                            value="{{ old('username', $user->username) }}"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            required
                        >
                        @error('username')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Peran Utama (Role Utama) -->
                    <div>
                        <label for="role_id" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Peran Utama (Default Role)</label>
                        <select name="role_id" id="role_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id) class="dark:bg-zinc-900">
                                    {{ $role->display_name }} ({{ 
                                        $role->name === 'tanse' ? 'school resilience affairs coordinator' : (
                                        $role->name === 'supervisor' ? 'religious affairs coordinator' : (
                                        $role->name === 'coordinator_tahfizh' ? 'tahfizh affairs coordinator' : 
                                        $role->name))
                                    }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-zinc-400 mt-1">Peran utama saat pertama kali login atau default sistem.</p>
                        @error('role_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Peran Tambahan / Rangkap (Additional Roles) -->
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-4 bg-zinc-50/50 dark:bg-zinc-800/30 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-bold text-zinc-800 dark:text-zinc-200">Peran Tambahan / Rangkap Tugas</label>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Centang peran tambahan jika user ini merangkap tugas. User dapat beralih peran via role switcher.</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Multi-Role</span>
                        </div>

                        @php
                            $assignedRoleIds = old('additional_role_ids', $user->roles->pluck('id')->all());
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-white dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 text-xs font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer hover:border-indigo-300 dark:hover:border-indigo-600 transition">
                                    <input type="checkbox" 
                                           name="additional_role_ids[]" 
                                           value="{{ $role->id }}"
                                           @checked(in_array($role->id, $assignedRoleIds) || old('role_id', $user->role_id) == $role->id)
                                           class="rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="truncate">{{ $role->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Status Akun</label>
                        <select name="status" id="status" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white" required>
                            <option value="active" @selected(old('status', $user->status) === 'active') class="dark:bg-zinc-900">Aktif</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive') class="dark:bg-zinc-900">Nonaktif</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ubah Password -->
                    <div>
                        <label for="password" class="block text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Password Baru (Teks Biasa)</label>
                        <input 
                            type="text" 
                            name="password" 
                            id="password" 
                            placeholder="Biarkan kosong jika tidak ingin diubah"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                        >
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Kosongkan jika tidak ada pergantian password. Jika diubah, password baru akan langsung disimpan dalam basis data terenkripsi dan dicatat plain-text.</p>
                        @error('password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-zinc-150 dark:border-zinc-800">
                        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md transition duration-150">
                            Perbarui Kredensial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
