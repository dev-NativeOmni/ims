<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                Manajemen User (Super Admin)
            </h2>
            <p class="text-sm text-gray-600">
                Lakukan force-reset password instan untuk akun pengguna apa pun.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Peran (Role)</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($users as $user)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                    <td class="px-6 py-4 font-mono text-xs text-zinc-500">
                                        {{ $user->id }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-zinc-900 dark:text-white font-semibold">
                                        {{ $user->username }}
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                        {{ $user->email ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $roleColor = 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200';
                                            if ($user->role?->name === 'super_admin') {
                                                $roleColor = 'bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-100 dark:border-purple-900/30';
                                            } elseif ($user->role?->name === 'admin') {
                                                $roleColor = 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30';
                                            } elseif ($user->role?->name === 'supervisor') {
                                                $roleColor = 'bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30';
                                            } elseif ($user->role?->name === 'teacher') {
                                                $roleColor = 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30';
                                            } elseif ($user->role?->name === 'student') {
                                                $roleColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $roleColor }}">
                                            {{ $user->role?->display_name ?: $user->role?->name ?: 'Tidak ada role' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('superadmin.users.force-reset', $user->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 border border-indigo-500/20 transition-all duration-150 cursor-pointer shadow-sm">
                                                Force Reset
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
