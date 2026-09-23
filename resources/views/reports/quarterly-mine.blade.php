<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Laporan Triwulan - Kelas Saya') }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                Download rekap triwulan untuk seluruh kelas/halaqoh yang Anda ampu, langsung dari data terbaru.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl border border-gray-100 dark:border-zinc-800 p-6">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="px-3 py-1 bg-indigo-500/10 text-indigo-500 rounded-full text-xs font-bold border border-indigo-500/20">
                        Tahun Ajaran {{ $academicYear }}
                    </span>
                    <span class="px-3 py-1 bg-emerald-500/10 text-emerald-600 rounded-full text-xs font-bold border border-emerald-500/20">
                        Triwulan {{ $selectedTerm }} ({{ implode(', ', $months) }})
                    </span>
                </div>

                <p class="text-sm text-gray-600 dark:text-zinc-400 mb-6">
                    Setiap file berisi rekap Presensi, Jurnal, Capaian Setoran per pekan, Grafik Ketuntasan Bulanan, Term/Indeks, dan Indeks Surah -- untuk semua kelas
                    di program tersebut yang punya murid Anda ampu. Kelas 10 otomatis memakai data Jilid/Halaman Ummi.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('reports.quarterly.export.mine', ['program' => 'reguler'] + request()->only(['academic_year', 'term'])) }}"
                       class="flex flex-col gap-2 rounded-xl border-2 border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-950/30 p-5 hover:border-indigo-400 transition">
                        <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-bold">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                            <span>Download Kelas Reguler Saya</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Semua kelas program Reguler (termasuk Kelas 10 / Ummi) yang Anda ampu.
                        </p>
                    </a>

                    <a href="{{ route('reports.quarterly.export.mine', ['program' => 'tahfizh'] + request()->only(['academic_year', 'term'])) }}"
                       class="flex flex-col gap-2 rounded-xl border-2 border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/30 p-5 hover:border-emerald-400 transition">
                        <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300 font-bold">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                            <span>Download Kelas Tahfizh Saya</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Semua halaqoh program Tahfizh/Akselerasi yang Anda ampu.
                        </p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
