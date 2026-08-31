<div id="ummiGrade10ReportCard" class="mx-auto max-w-4xl rounded-[32px] p-6 sm:p-10 shadow-2xl relative overflow-hidden font-sans border-[6px] border-amber-400" style="background-color: #ffffff !important; color: #0f172a !important;">
    
    <!-- Top Header Bar with Uploaded Logos -->
    <div class="flex items-center justify-between border-b-2 border-amber-300 pb-5 mb-6 gap-4">
        <!-- Circle School Emblem Logo -->
        <div class="shrink-0">
            <img src="{{ asset('images/logo-alazhar7-circle.png') }}" alt="Logo Al Azhar 7" class="w-16 h-16 sm:w-20 sm:h-20 object-contain">
        </div>

        <!-- Banner Gemilang Logo & Subtitle -->
        <div class="text-right shrink-0">
            <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="SMA Islam Al Azhar 7 GEMILANG" class="h-10 sm:h-14 object-contain ml-auto">
        </div>
    </div>

    <!-- Main Title Section -->
    <div class="text-center my-5">
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-wide uppercase leading-none" style="color: #0f172a !important;">
            LAPORAN CAPAIAN TAHFIDZ
        </h1>

        <!-- Subtitle Badge UMMI -->
        <div class="inline-flex items-center gap-2 bg-slate-900 text-amber-400 text-xs sm:text-sm font-black px-6 py-2 rounded-2xl shadow-md mt-3 border border-amber-400 uppercase tracking-widest" style="background-color: #0f172a !important; color: #fbbf24 !important;">
            <span>❖</span> PEMBELAJARAN UMMI <span>❖</span>
        </div>

        <!-- Badges: Kelas & Bulan -->
        <div class="flex justify-center items-center gap-3 mt-4 flex-wrap">
            <div class="bg-blue-800 text-white font-extrabold text-xs sm:text-sm px-5 py-1.5 rounded-xl shadow-sm border border-blue-600 uppercase" style="background-color: #1e40af !important; color: #ffffff !important;">
                KELAS {{ $selectedClass?->name ?? '10' }}
            </div>
            <div class="bg-amber-600 text-white font-extrabold text-xs sm:text-sm px-5 py-1.5 rounded-xl shadow-sm border border-amber-500 uppercase" style="background-color: #d97706 !important; color: #ffffff !important;">
                BULAN {{ strtoupper($monthName ?? ($monthsList[$selectedMonth] ?? '')) }} {{ $selectedYear ?? date('Y') }}
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="mt-6 overflow-hidden rounded-2xl border-2 border-emerald-700 shadow-md" style="background-color: #ffffff !important;">
        <table class="w-full text-left border-collapse text-xs sm:text-sm" style="background-color: #ffffff !important; color: #0f172a !important;">
            <thead>
                <tr class="bg-emerald-700 text-white text-center font-bold tracking-wide" style="background-color: #047857 !important; color: #ffffff !important;">
                    <th class="py-3.5 px-2 border-r border-emerald-600 w-12">No</th>
                    <th class="py-3.5 px-3 border-r border-emerald-600 text-left">Nama Murid</th>
                    <th class="py-3.5 px-2 border-r border-emerald-600 w-16">Jilid</th>
                    <th class="py-3.5 px-2 border-r border-emerald-600 w-20">Halaman</th>
                    <th class="py-3.5 px-3 border-r border-emerald-600 text-left">Capaian Hafalan</th>
                    <th class="py-3.5 px-3 text-left">Ziyadah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200" style="color: #0f172a !important;">
                @forelse ($studentReports as $idx => $row)
                    <tr style="background-color: {{ $idx % 2 === 0 ? '#ffffff' : '#f0fdf4' }} !important; color: #0f172a !important;">
                        <td class="py-3 px-2 text-center border-r border-zinc-200 font-bold" style="color: #334155 !important;">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-3 px-3 border-r border-zinc-200 font-bold" style="color: #0f172a !important;">
                            {{ $row['student']->name }}
                        </td>
                        <td class="py-3 px-2 text-center border-r border-zinc-200 font-black" style="color: #1e3a8a !important;">
                            {{ $row['ummi_jilid'] ?: '-' }}
                        </td>
                        <td class="py-3 px-2 text-center border-r border-zinc-200 font-black" style="color: #1e3a8a !important;">
                            {{ $row['ummi_halaman'] ?: '-' }}
                        </td>
                        <td class="py-3 px-3 border-r border-zinc-200 font-semibold" style="color: #1e293b !important;">
                            {{ $row['ummi_capaian'] ?: '-' }}
                        </td>
                        <td class="py-3 px-3 font-semibold" style="color: #0369a1 !important;">
                            {{ $row['ziyadah'] ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr style="background-color: #ffffff !important;">
                        <td colspan="6" class="py-8 text-center text-xs font-semibold" style="color: #64748b !important;">
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
        <div class="rounded-2xl p-3.5 shadow-md flex items-center gap-3 max-w-lg border-2 border-amber-400" style="background-color: #0f172a !important; color: #ffffff !important;">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-lg shrink-0" style="background-color: #d97706 !important; color: #0f172a !important;">
                📖
            </div>
            <div>
                <p class="font-bold text-xs tracking-wide" style="color: #fbbf24 !important;">"خَيْرُكُمْ مَنْ تَعَلَّمَ الْقُرْآنَ وَعَلَّمَهُ"</p>
                <p class="text-[11px] italic mt-0.5" style="color: #e2e8f0 !important;">"Sebaik-baik kalian adalah yang belajar Al-Qur'an dan mengajarkannya." (HR. Bukhari)</p>
            </div>
        </div>

        <!-- Right: Social Media Contacts -->
        <div class="text-right text-[11px] font-semibold space-y-0.5" style="color: #334155 !important;">
            <p class="font-bold text-base" style="color: #0f172a !important;">SMA Islam Al Azhar 7 Solo Baru</p>
            <p>🌐 smaialazhar7.sch.id | 📞 0812-2347-0077</p>
            <p class="text-[10px]" style="color: #64748b !important;">@smaialazhar7</p>
        </div>
    </div>
</div>
