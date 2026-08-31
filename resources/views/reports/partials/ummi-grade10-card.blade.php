<div id="ummiGrade10ReportCard" class="mx-auto max-w-4xl bg-gradient-to-b from-sky-50 via-white to-blue-50 border-[6px] border-amber-400 rounded-[32px] p-6 sm:p-10 shadow-2xl text-zinc-900 relative overflow-hidden font-sans">
    
    <!-- Header Decorative Bar -->
    <div class="flex items-center justify-between border-b-2 border-amber-300 pb-5 mb-6">
        <!-- School Logo & Branding -->
        <div class="flex items-center gap-3">
            <div class="w-14 h-14 bg-indigo-900 text-white rounded-full flex items-center justify-center font-bold text-xs shadow-md border-2 border-amber-400 shrink-0">
                AL AZHAR 7
            </div>
            <div>
                <h3 class="text-base font-extrabold text-indigo-950 tracking-tight leading-tight">
                    SMA Islam Al Azhar 7 Solo Baru
                </h3>
                <p class="text-[11px] font-bold text-amber-600 tracking-wider uppercase">
                    <span class="bg-indigo-900 text-white px-1.5 py-0.5 rounded text-[9px] mr-1">GEMILANG</span> Generasi Mulia Islami Cemerlang
                </p>
            </div>
        </div>

        <!-- Right Side Contact / Web -->
        <div class="text-right text-[11px] text-zinc-600 hidden sm:block font-medium">
            <p class="font-bold text-indigo-900">IMS — HafizPlus 2.0</p>
            <p>smaialazhar7.sch.id</p>
        </div>
    </div>

    <!-- Main Title Section -->
    <div class="text-center my-4">
        <h1 class="text-2xl sm:text-4xl font-black text-indigo-950 tracking-wide uppercase leading-none">
            LAPORAN CAPAIAN TAHFIDZ
        </h1>

        <!-- Subtitle Badge UMMI -->
        <div class="inline-flex items-center gap-2 bg-indigo-950 text-white text-xs sm:text-sm font-black px-6 py-2 rounded-2xl shadow-md mt-3 border border-amber-400 uppercase tracking-widest">
            <span class="text-amber-400">❖</span> PEMBELAJARAN UMMI <span class="text-amber-400">❖</span>
        </div>

        <!-- Badges: Kelas & Bulan -->
        <div class="flex justify-center items-center gap-3 mt-4 flex-wrap">
            <div class="bg-indigo-800 text-white font-extrabold text-xs sm:text-sm px-5 py-1.5 rounded-xl shadow-sm border border-indigo-600 uppercase">
                KELAS {{ $selectedClass?->name ?? '10' }}
            </div>
            <div class="bg-amber-500 text-white font-extrabold text-xs sm:text-sm px-5 py-1.5 rounded-xl shadow-sm border border-amber-400 uppercase">
                BULAN {{ strtoupper($monthName ?? ($monthsList[$selectedMonth] ?? '')) }} {{ $selectedYear ?? date('Y') }}
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="mt-6 overflow-hidden rounded-2xl border-2 border-emerald-700 shadow-lg bg-white">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
            <thead>
                <tr class="bg-emerald-700 text-white text-center font-bold tracking-wide">
                    <th class="py-3 px-2 border-r border-emerald-600 w-12">No</th>
                    <th class="py-3 px-3 border-r border-emerald-600 text-left">Nama Murid</th>
                    <th class="py-3 px-2 border-r border-emerald-600 w-16">Jilid</th>
                    <th class="py-3 px-2 border-r border-emerald-600 w-20">Halaman</th>
                    <th class="py-3 px-3 border-r border-emerald-600 text-left">Capaian Hafalan</th>
                    <th class="py-3 px-3 text-left">Ziyadah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 text-zinc-900 font-medium">
                @forelse ($studentReports as $idx => $row)
                    <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-emerald-50/30' }} hover:bg-emerald-50 transition duration-150">
                        <td class="py-2.5 px-2 text-center border-r border-zinc-200 font-bold text-zinc-600">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-2.5 px-3 border-r border-zinc-200 font-bold text-zinc-900">
                            {{ $row['student']->name }}
                        </td>
                        <td class="py-2.5 px-2 text-center border-r border-zinc-200 font-extrabold text-indigo-900">
                            {{ $row['ummi_jilid'] ?: '-' }}
                        </td>
                        <td class="py-2.5 px-2 text-center border-r border-zinc-200 font-extrabold text-indigo-900">
                            {{ $row['ummi_halaman'] ?: '-' }}
                        </td>
                        <td class="py-2.5 px-3 border-r border-zinc-200 font-semibold text-zinc-800">
                            {{ $row['ummi_capaian'] ?: '-' }}
                        </td>
                        <td class="py-2.5 px-3 font-semibold text-indigo-700">
                            {{ $row['ziyadah'] ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs text-zinc-500 font-semibold">
                            Belum ada data catatan Pembelajaran UMMI untuk kelas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Hadits Emblem & Social Info -->
    <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t-2 border-amber-300">
        <!-- Left: Hadits Emblem Badge -->
        <div class="bg-indigo-950 text-white border-2 border-amber-400 rounded-2xl p-3.5 shadow-md flex items-center gap-3 max-w-lg">
            <div class="w-10 h-10 bg-amber-500 text-indigo-950 rounded-full flex items-center justify-center font-black text-lg shrink-0">
                📖
            </div>
            <div>
                <p class="font-bold text-xs text-amber-300 tracking-wide">"خَيْرُكُمْ مَنْ تَعَلَّمَ الْقُرْآنَ وَعَلَّمَهُ"</p>
                <p class="text-[11px] text-zinc-200 italic mt-0.5">"Sebaik-baik kalian adalah yang belajar Al-Qur'an dan mengajarkannya." (HR. Bukhari)</p>
            </div>
        </div>

        <!-- Right: Social Media Contacts -->
        <div class="text-right text-[11px] text-zinc-600 font-semibold space-y-0.5">
            <p class="text-indigo-950 font-bold">SMA Islam Al Azhar 7 Solo Baru</p>
            <p>🌐 smaialazhar7.sch.id | 📞 0812-2347-0077</p>
            <p class="text-[10px] text-zinc-400">@smaialazhar7</p>
        </div>
    </div>
</div>
