{{--
    Tabel Tahfizh susunan lama (beberapa target + kotak Nilai Akhir), hanya untuk rapor yang
    dikunci sebelum tabel satu-baris-per-triwulan dipakai -- simpanannya masih berbentuk 'rows'.
--}}
    <!-- Table 1: Targets & Capaian Terakhir -->
    <table class="w-full table-fixed border border-black text-xs text-left">
        <thead>
            <tr class="bg-gray-100 border-b border-black text-center font-bold">
                <th class="p-1.5 border-r border-black w-[6%]">No.</th>
                <th class="p-1.5 border-r border-black w-[36%]">TARGET SURAH YANG DIHAFAL</th>
                <th class="p-1.5 border-r border-black w-[36%]">CAPAIAN TERAKHIR YANG DIHAFALKAN</th>
                <th class="p-1.5 border-r border-black w-[10%]">STATUS</th>
                <th class="p-1.5 w-[12%]">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sheet['tahfizh']['rows'] as $idx => $row)
                <tr class="border-b border-black">
                    <td class="p-1.5 border-r border-black text-center align-middle">{{ $idx + 1 }}</td>
                    {{-- Isi sel berupa HTML tersimpan saat dikunci (nilai sudah di-escape saat dibuat). --}}
                    <td class="p-1.5 border-r border-black align-middle font-semibold">{!! $row['target'] !!}</td>
                    <td class="p-1.5 border-r border-black align-middle font-semibold">{!! $row['capaian'] !!}</td>
                    <td class="p-1.5 border-r border-black text-center align-middle font-bold text-black">
                        {{ $row['completed'] ? 'Tuntas' : 'Dalam Proses' }}
                    </td>
                    <td class="p-1.5 align-middle text-gray-700">
                        {{ $row['notes'] ?: ($row['completed'] ? 'Tercapai.' : 'Sedang berjalan.') }}
                    </td>
                </tr>
            @empty
                <tr class="border-b border-black">
                    <td colspan="5" class="p-3 text-center text-gray-500 italic">{{ $sheet['tahfizh']['empty_text'] }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Nilai Akhir Tahfizh -->
    <div class="border border-black rounded p-3 mt-4 flex items-center justify-between bg-gray-50">
        <div class="text-xs font-black uppercase">Nilai Akhir Tahfizh</div>
        <div class="text-2xl font-black">{{ $sheet['tahfizh']['final_score'] }}<span class="text-xs font-semibold"> / 100</span></div>
    </div>
