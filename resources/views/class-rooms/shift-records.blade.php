<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Pindah / Koreksi Jadwal Setoran
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Pindahkan seluruh catatan setoran (Hafalan, Ummi, Muraja'ah, dan Presensi) dari satu hari jadwal ke hari jadwal yang baru pada bulan tertentu.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Alert Notification -->
            @if (session('error'))
                <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl p-4 flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                    <span class="text-sm text-red-800 dark:text-red-300 font-semibold">{{ session('error') }}</span>
                </div>
            @endif

            @include('partials.academic-calendar-tabs')

            @if ($isMonthLocked)
                <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-2xl p-4 flex items-start gap-3 shadow-xs">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div class="text-xs text-amber-800 dark:text-amber-300">
                        <strong class="font-bold">Informasi:</strong> Kalender &amp; jadwal bulan {{ $monthsList[$currentMonth] }} {{ $currentYear }} saat ini berstatus <span class="font-bold underline">Terkunci</span>. Pastikan Anda telah memeriksa data dengan teliti sebelum mengeksekusi pemindahan.
                    </div>
                </div>
            @endif

            <!-- Form Pemilihan Parameter Pemindahan -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl p-6 shadow-xs space-y-6">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="w-5 h-5 text-teal-600" />
                        <span>Parameter Pemindahan Catatan</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                        Pilih kelas, bulan/tahun, serta hari asal (hari yang salah diinput) dan hari tujuan (hari jadwal yang fix).
                    </p>
                </div>

                <form method="GET" action="{{ route('class-schedules.shift') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Pilih Kelas -->
                    <div class="lg:col-span-2">
                        <label for="class_room_id" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Kelas
                        </label>
                        <select name="class_room_id" id="class_room_id" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold py-2.5 px-3 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($classRooms as $c)
                                <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->program?->name ?? 'Reguler' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bulan & Tahun -->
                    <div>
                        <label for="month" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Bulan
                        </label>
                        <select name="month" id="month" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold py-2.5 px-3 focus:ring-teal-500 focus:border-teal-500">
                            @foreach ($monthsList as $mNum => $mName)
                                <option value="{{ $mNum }}" {{ $mNum == $currentMonth ? 'selected' : '' }}>
                                    {{ $mName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="year" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Tahun
                        </label>
                        <select name="year" id="year" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold py-2.5 px-3 focus:ring-teal-500 focus:border-teal-500">
                            @foreach (\App\Support\AcademicYear::calendarYears(2) as $y)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hari Asal & Hari Tujuan -->
                    <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 pt-2 border-t border-gray-100 dark:border-zinc-800/80">
                        <div class="lg:col-span-2">
                            <label for="from_day" class="block text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Hari Asal (Yang Sudah Diinput)</span>
                            </label>
                            <select name="from_day" id="from_day" class="w-full rounded-xl border-rose-300 dark:border-rose-800/60 dark:bg-zinc-800 dark:text-white text-sm font-semibold py-2.5 px-3 focus:ring-rose-500 focus:border-rose-500">
                                @foreach ($daysOfWeek as $dNum => $dName)
                                    <option value="{{ $dNum }}" {{ $dNum == $fromDay ? 'selected' : '' }}>
                                        Hari {{ $dName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-center pt-6 hidden lg:flex">
                            <x-heroicon-o-arrow-right class="w-6 h-6 text-gray-400 dark:text-zinc-500" />
                        </div>

                        <div class="lg:col-span-2">
                            <label for="to_day" class="block text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Hari Tujuan (Jadwal Fix / Baru)</span>
                            </label>
                            <select name="to_day" id="to_day" class="w-full rounded-xl border-emerald-300 dark:border-emerald-800/60 dark:bg-zinc-800 dark:text-white text-sm font-semibold py-2.5 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                @foreach ($daysOfWeek as $dNum => $dName)
                                    <option value="{{ $dNum }}" {{ $dNum == $toDay ? 'selected' : '' }}>
                                        Hari {{ $dName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="lg:col-span-5 flex justify-end gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-bold shadow-sm transition cursor-pointer">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            <span>Tinjau / Preview Catatan</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Data & Konfirmasi Eksekusi -->
            @if ($previewData !== null && $selectedClass !== null)
                <div class="space-y-6">
                    <!-- KPI Cards Summary -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 shadow-xs">
                            <span class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Murid Terlibat</span>
                            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $previewData['students_count'] }}</p>
                        </div>
                        <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/30 rounded-2xl p-4 shadow-xs">
                            <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Setoran Hafalan</span>
                            <p class="text-2xl font-black text-indigo-900 dark:text-indigo-300 mt-1">{{ $previewData['total_hafalan'] }}</p>
                        </div>
                        <div class="bg-teal-50/50 dark:bg-teal-950/20 border border-teal-200 dark:border-teal-900/30 rounded-2xl p-4 shadow-xs">
                            <span class="text-[11px] font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider">Setoran Ummi</span>
                            <p class="text-2xl font-black text-teal-900 dark:text-teal-300 mt-1">{{ $previewData['total_ummi'] }}</p>
                        </div>
                        <div class="bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-2xl p-4 shadow-xs">
                            <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Muraja'ah</span>
                            <p class="text-2xl font-black text-amber-900 dark:text-amber-300 mt-1">{{ $previewData['total_murajaah'] }}</p>
                        </div>
                        <div class="bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-900/30 rounded-2xl p-4 shadow-xs">
                            <span class="text-[11px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">Presensi</span>
                            <p class="text-2xl font-black text-purple-900 dark:text-purple-300 mt-1">{{ $previewData['total_attendance'] }}</p>
                        </div>
                    </div>

                    <!-- Breakdown Per Tanggal Pekan -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl shadow-xs overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                            <div>
                                <h4 class="font-extrabold text-sm text-gray-900 dark:text-white">
                                    Daftar Tanggal Pergeseran &ndash; Kelas {{ $selectedClass->name }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                                    Setiap tanggal hari {{ $daysOfWeek[$fromDay] }} pada pekan tersebut akan dipindahkan ke tanggal hari {{ $daysOfWeek[$toDay] }}.
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-teal-100 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 font-extrabold text-xs rounded-full">
                                Total {{ $previewData['total_records'] }} Catatan Ditemukan
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-zinc-850/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Pekan</th>
                                        <th class="px-4 py-3 text-left">Tanggal Asal ({{ $daysOfWeek[$fromDay] }})</th>
                                        <th class="px-4 py-3 text-center"></th>
                                        <th class="px-4 py-3 text-left">Tanggal Baru ({{ $daysOfWeek[$toDay] }})</th>
                                        <th class="px-3 py-3 text-center">Hafalan</th>
                                        <th class="px-3 py-3 text-center">Ummi</th>
                                        <th class="px-3 py-3 text-center">Muraja'ah</th>
                                        <th class="px-3 py-3 text-center">Presensi</th>
                                        <th class="px-4 py-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                    @forelse ($previewData['dates'] as $idx => $d)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition">
                                            <td class="px-4 py-3 font-bold text-gray-700 dark:text-zinc-300">
                                                Pekan {{ $idx + 1 }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-bold text-rose-600 dark:text-rose-400">
                                                    {{ $d['from_date']->translatedFormat('l, d F Y') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-400">
                                                <x-heroicon-o-arrow-right class="w-4 h-4 mx-auto" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                                    {{ $d['to_date']->translatedFormat('l, d F Y') }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-center font-semibold text-gray-800 dark:text-zinc-200">
                                                {{ $d['hafalan_count'] }}
                                            </td>
                                            <td class="px-3 py-3 text-center font-semibold text-gray-800 dark:text-zinc-200">
                                                {{ $d['ummi_count'] }}
                                            </td>
                                            <td class="px-3 py-3 text-center font-semibold text-gray-800 dark:text-zinc-200">
                                                {{ $d['murajaah_count'] }}
                                            </td>
                                            <td class="px-3 py-3 text-center font-semibold text-gray-800 dark:text-zinc-200">
                                                {{ $d['attendance_count'] }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-extrabold text-gray-900 dark:text-white">
                                                {{ $d['total'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                                Tidak ada tanggal hari {{ $daysOfWeek[$fromDay] }} pada bulan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Eksekusi Pemindahan -->
                        <div class="p-6 bg-gray-50/70 dark:bg-zinc-850/40 border-t border-gray-100 dark:border-zinc-800 space-y-4">
                            <form method="POST" action="{{ route('class-schedules.shift.execute') }}" onsubmit="return confirm('Apakah Anda yakin ingin memindahkan seluruh catatan setoran dan presensi kelas {{ $selectedClass->name }} dari hari {{ $daysOfWeek[$fromDay] }} ke hari {{ $daysOfWeek[$toDay] }} pada bulan {{ $monthsList[$currentMonth] }} {{ $currentYear }}?')">
                                @csrf
                                <input type="hidden" name="class_room_id" value="{{ $selectedClass->id }}">
                                <input type="hidden" name="year" value="{{ $currentYear }}">
                                <input type="hidden" name="month" value="{{ $currentMonth }}">
                                <input type="hidden" name="from_day" value="{{ $fromDay }}">
                                <input type="hidden" name="to_day" value="{{ $toDay }}">

                                <div class="space-y-3">
                                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                        Pilih Jenis Catatan yang Ingin Dipindahkan:
                                    </h5>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-zinc-300 cursor-pointer">
                                            <input type="checkbox" name="shift_types[]" value="hafalan" checked class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span>Setoran Hafalan Reguler</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-zinc-300 cursor-pointer">
                                            <input type="checkbox" name="shift_types[]" value="ummi" checked class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span>Setoran Metode Ummi (termasuk hitung ulang Tatap Muka)</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-zinc-300 cursor-pointer">
                                            <input type="checkbox" name="shift_types[]" value="murajaah" checked class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span>Catatan Muraja'ah</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-zinc-300 cursor-pointer">
                                            <input type="checkbox" name="shift_types[]" value="attendance" checked class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span>Presensi Kehadiran Kelas</span>
                                        </label>
                                    </div>

                                    <div class="pt-2">
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-indigo-700 dark:text-indigo-400 cursor-pointer bg-indigo-50 dark:bg-indigo-950/30 px-3 py-2 rounded-xl border border-indigo-200 dark:border-indigo-900/40">
                                            <input type="checkbox" name="update_schedule" value="1" checked class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Sekaligus perbarui jadwal rutin/pekanan kelas {{ $selectedClass->name }} dari hari {{ $daysOfWeek[$fromDay] }} ke hari {{ $daysOfWeek[$toDay] }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-zinc-800">
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">
                                        Perpindahan ini aman dan otomatis memperbarui audit log serta sinkronisasi target capaian.
                                    </div>
                                    <button type="submit" @disabled($previewData['total_records'] === 0) class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-extrabold shadow-sm transition cursor-pointer">
                                        <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                                        <span>Eksekusi Pemindahan ({{ $previewData['total_records'] }} Catatan)</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
