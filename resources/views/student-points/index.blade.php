<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Poin & Disiplin Santri
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Pencatatan pelanggaran tata tertib dan penghargaan prestasi santri.
                </p>
            </div>
            
            @if ($canManage)
                <a href="{{ route('student-points.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Catat Poin Baru</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Statistics Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Violations Card -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex items-center justify-between transition-all hover:shadow-md">
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total Poin Pelanggaran</p>
                        <h3 class="text-3xl font-extrabold text-red-600 dark:text-rose-500 mt-2">{{ $totalViolations }}</h3>
                        <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Akumulasi pelanggaran tata tertib</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-rose-950/30 flex items-center justify-center text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <!-- Total Rewards Card -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex items-center justify-between transition-all hover:shadow-md">
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total Poin Penghargaan</p>
                        <h3 class="text-3xl font-extrabold text-green-600 dark:text-emerald-500 mt-2">{{ $totalRewards }}</h3>
                        <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Akumulasi prestasi & kebaikan</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-emerald-950/30 flex items-center justify-center text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-2.25a1.125 1.125 0 00-1.125 1.125V18.75m9 0a9 9 0 11-18 0M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Net Score Card -->
                @php $balance = $totalRewards - $totalViolations; @endphp
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex items-center justify-between transition-all hover:shadow-md">
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Selisih Kebaikan (Net)</p>
                        <h3 class="text-3xl font-extrabold mt-2 {{ $balance >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                            {{ $balance > 0 ? '+' : '' }}{{ $balance }}
                        </h3>
                        <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Total penghargaan dikurangi pelanggaran</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center text-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filter & Search (no-print) -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm transition-colors duration-200">
                <form method="GET" action="{{ route('student-points.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="search" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Cari Santri</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau NIS santri..."
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                    </div>

                    <div class="w-full sm:w-48">
                        <label for="type" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tipe Poin</label>
                        <select name="type" id="type" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Semua Tipe</option>
                            <option value="violation" {{ request('type') === 'violation' ? 'selected' : '' }}>Pelanggaran</option>
                            <option value="reward" {{ request('type') === 'reward' ? 'selected' : '' }}>Penghargaan</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors min-h-[42px]">
                            Filter
                        </button>
                        @if (request()->anyFilled(['search', 'type']))
                            <a href="{{ route('student-points.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors min-h-[42px]">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- List Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Poin Kedisiplinan</h3>
                </div>

                @if ($points->isEmpty())
                    <div class="p-8 text-center text-sm text-gray-500 dark:text-zinc-500">
                        Tidak ada riwayat catatan poin disiplin ditemukan.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                            <thead class="bg-gray-50 dark:bg-[#09090b]/40">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Santri</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kategori / Judul</th>
                                    <th scope="col" class="px-6 py-3.5 class text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tipe</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Poin</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Dicatat Oleh</th>
                                    @if ($canManage)
                                        <th scope="col" class="px-6 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900 transition-colors duration-200">
                                @foreach ($points as $item)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors">
                                        <!-- Tanggal -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-zinc-300">
                                            {{ $item->date?->format('d/m/Y') }}
                                        </td>
                                        
                                        <!-- Santri -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                            <div>{{ $item->student?->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium">Kelas: {{ $item->student?->classRoom?->name ?? '-' }}</div>
                                        </td>

                                        <!-- Judul / Deskripsi -->
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-zinc-300">
                                            <div class="font-medium text-gray-900 dark:text-zinc-200">{{ $item->title }}</div>
                                            @if ($item->description)
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-1" title="{{ $item->description }}">{{ $item->description }}</p>
                                            @endif
                                        </td>

                                        <!-- Tipe Badge -->
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-900/40 uppercase">
                                                        Pelanggaran
                                                </span>
                                            @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/40 uppercase">
                                                        Penghargaan
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Poin -->
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-extrabold">
                                            <span class="{{ $item->type === 'violation' ? 'text-red-600 dark:text-rose-500' : 'text-green-600 dark:text-emerald-500' }}">
                                                {{ $item->type === 'violation' ? '-' : '+' }}{{ $item->points }}
                                            </span>
                                        </td>

                                        <!-- Logger -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-zinc-400">
                                            {{ $item->logger?->name ?? 'Sistem' }}
                                        </td>

                                        <!-- Aksi -->
                                        @if ($canManage)
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="inline-flex gap-2">
                                                    <a href="{{ route('student-points.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Ubah">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 20.013a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                                        </svg>
                                                    </a>
                                                    
                                                    <form method="POST" action="{{ route('student-points.destroy', $item) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan poin ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 cursor-pointer" title="Hapus">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($points->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-[#09090b]/40">
                            {{ $points->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
