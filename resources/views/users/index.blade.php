<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Manajemen Akun User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Deskripsi Panel -->
            <div class="bg-gradient-to-r from-violet-600 via-indigo-650 to-indigo-700 text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-12 translate-y-12">
                    <svg class="h-64 w-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-xl font-bold mb-2">Panel Kontrol Akun Super Admin</h3>
                    <p class="text-violet-100 text-sm leading-relaxed">
                        Manajemen kredensial seluruh pengguna HafizPlus. Anda dapat melihat username, mencatat/memperbarui password dalam teks biasa, serta mengontrol peran dan status aktifasi seluruh akun dari halaman monitoring ini.
                    </p>
                </div>
            </div>

            <!-- Filter & Pencarian -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Cari User</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau username..."
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400"
                        >
                    </div>

                    <div>
                        <label for="role_id" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Peran (Role)</label>
                        <select name="role_id" id="role_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                            <option value="" class="dark:bg-zinc-900">Semua Peran</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id) class="dark:bg-zinc-900">
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex items-end gap-3">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-650 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition duration-150 shadow-sm">
                            Cari User
                        </button>

                        <a href="{{ route('users.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm font-semibold transition duration-150">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- List Users Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-6 py-4">Nama Lengkap</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Peran (Role)</th>
                                <th class="px-6 py-4">Password (Teks Biasa)</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($users as $u)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150" x-data="{ showPass: false }">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $u->name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-450">
                                        {{ $u->username }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $roleColor = 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200';
                                            if ($u->role?->name === 'super_admin') {
                                                $roleColor = 'bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-100 dark:border-purple-900/30';
                                            } elseif ($u->role?->name === 'admin') {
                                                $roleColor = 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30';
                                            } elseif ($u->role?->name === 'supervisor') {
                                                $roleColor = 'bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30';
                                            } elseif ($u->role?->name === 'teacher') {
                                                $roleColor = 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30';
                                            } elseif ($u->role?->name === 'student') {
                                                $roleColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $roleColor }}">
                                            {{ $u->role?->display_name ?: 'Tidak ada role' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm tracking-wide bg-zinc-50 dark:bg-zinc-850 px-2 py-1 rounded border dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 select-all" x-text="showPass ? '{{ $u->plain_password ?: '(Belum Tersimpan)' }}' : '••••••••'"></span>
                                            <button @click="showPass = !showPass" type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 focus:outline-none">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($u->isActive())
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-450">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('users.edit', $u) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition duration-150">
                                            Edit Kredensial
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data user ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
