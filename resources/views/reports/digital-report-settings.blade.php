<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-zinc-900 dark:text-zinc-100 leading-tight">
                    Pengaturan Rapor Digital & Cetak Per Kelas
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Konfigurasi komponen rapor, tahun ajaran aktif, dan opsi cetak massal rapor per kelas.
                </p>
            </div>
            <a href="{{ route('digital-reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition">
                ← Kembali ke Daftar Rapor
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl text-emerald-700 dark:text-emerald-300 text-sm font-semibold flex items-center gap-2">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            {{-- Form Pengaturan Rapor Digital --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white border-b pb-3 mb-5 dark:border-zinc-800 flex items-center gap-2">
                    <span>⚙️</span> Konfigurasi Periode & Komponen Rapor Digital
                </h3>

                <form method="POST" action="{{ route('digital-reports.settings.update') }}" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="academic_year" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tahun Ajaran Aktif</label>
                            <input type="text" name="academic_year" id="academic_year" value="{{ old('academic_year', $academicYear) }}" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: 2025/2026">
                        </div>

                        <div>
                            <label for="semester" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Semester Aktif</label>
                            <select name="semester" id="semester" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1" @selected((int)$semester === 1)>Semester 1 (Ganjil)</option>
                                <option value="2" @selected((int)$semester === 2)>Semester 2 (Genap)</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t pt-5 dark:border-zinc-800 space-y-3">
                        <label class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider">Modul Komponen Rapor yang Ditampilkan</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 transition">
                                <input type="checkbox" name="report_show_tahfizh" value="1" @checked($showTahfizh) class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-bold text-gray-800 dark:text-zinc-200">📖 Laporan Tahfizh</span>
                            </label>

                            <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 transition">
                                <input type="checkbox" name="report_show_adab" value="1" @checked($showAdab) class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-bold text-gray-800 dark:text-zinc-200">🕋 Penilaian Adab</span>
                            </label>

                            <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 transition">
                                <input type="checkbox" name="report_show_tanse" value="1" @checked($showTanse) class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-bold text-gray-800 dark:text-zinc-200">🛡️ Laporan Tanse</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-sm transition">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Opsi Print Rapor Per Kelas --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="border-b pb-3 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🖨️</span> Opsi Cetak Rapor Per Kelas (Batch Print Rapor)
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Cetak seluruh rapor murid dalam 1 kelas secara lengkap sekaligus dalam satu dokumen siap cetak/PDF.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($classRooms as $cRoom)
                        <div class="p-4 bg-gray-50 dark:bg-zinc-800/50 rounded-2xl border border-gray-200 dark:border-zinc-800 flex flex-col justify-between space-y-3">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-base">{{ $cRoom->name }}</h4>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Program: {{ $cRoom->program?->name ?: '-' }}</p>
                            </div>
                            <a href="{{ route('digital-reports.class-print', ['classRoom' => $cRoom->id, 'academic_year' => $academicYear, 'semester' => $semester]) }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition w-full">
                                <span>🖨️</span> Cetak Rapor Seluruh Kelas
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-4 col-span-full">Belum ada kelas terdaftar.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
