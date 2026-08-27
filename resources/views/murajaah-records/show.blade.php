<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                    Detail Muraja'ah
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $murajaahRecord->student?->name }} • {{ $murajaahRecord->reviewed_at?->format('d M Y') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $murajaahRecord->whatsapp_share_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150">
                    <span>💬 Kirim Laporan via WhatsApp</span>
                </a>

                <a href="{{ route('murajaah-records.edit', $murajaahRecord) }}"
                   class="btn-action-edit inline-flex items-center gap-1 px-3.5 py-2">
                    ✏️ Edit
                </a>

                <a href="{{ route('murajaah-records.index') }}"
                   class="btn-action-detail inline-flex items-center gap-1 px-3.5 py-2">
                    ← Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Card Utama Murajaah -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-2xl p-6 space-y-6">
                
                <!-- Header Card: Status & Nilai Keseluruhan -->
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block">Status Muraja'ah</span>
                        <span class="inline-block mt-1 px-3 py-1 rounded-xl text-xs font-bold
                            {{ $murajaahRecord->status === 'passed' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : '' }}
                            {{ $murajaahRecord->status === 'repeat' ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : '' }}
                            {{ $murajaahRecord->status === 'needs_improvement' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : '' }}
                        ">
                            {{ $murajaahRecord->status_label }}
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block">Nilai Keseluruhan</span>
                        <div class="mt-1 flex items-baseline gap-1 justify-end">
                            <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                                {{ $murajaahRecord->overall_score !== null ? number_format((float) $murajaahRecord->overall_score, 0) : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Grid Data Murajaah -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 text-sm">
                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Murid</span>
                        <p class="font-bold text-zinc-900 dark:text-white mt-1">{{ $murajaahRecord->student?->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $murajaahRecord->student?->classRoom?->name ?: '-' }} ({{ $murajaahRecord->student?->student_number ?? '-' }})</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Guru / Pembimbing</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">{{ $murajaahRecord->teacher?->user?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Tanggal Muraja'ah</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">{{ $murajaahRecord->reviewed_at?->translatedFormat('l, j F Y') }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Surah</span>
                        <p class="font-bold text-zinc-900 dark:text-white mt-1">
                            {{ $murajaahRecord->surah?->number }}. {{ $murajaahRecord->surah?->name_latin }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Rentang Ayat</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">
                            Ayat {{ $murajaahRecord->ayah_range }}
                        </p>
                    </div>
                </div>

                <!-- Grid Rincian Nilai -->
                <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-4 border border-zinc-100 dark:border-zinc-800 space-y-2">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block">Rincian Penilaian</span>
                    <div class="grid grid-cols-3 gap-3 text-center pt-2">
                        <div class="p-2.5 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                            <span class="text-[11px] text-zinc-400 block font-semibold">Kelancaran</span>
                            <span class="font-bold text-base text-zinc-900 dark:text-white">{{ $murajaahRecord->fluency_score ?? '-' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                            <span class="text-[11px] text-zinc-400 block font-semibold">Tajwid</span>
                            <span class="font-bold text-base text-zinc-900 dark:text-white">{{ $murajaahRecord->tajwid_score ?? '-' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                            <span class="text-[11px] text-zinc-400 block font-semibold">Makhraj</span>
                            <span class="font-bold text-base text-zinc-900 dark:text-white">{{ $murajaahRecord->makhraj_score ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Catatan Evaluasi -->
                <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-4 border border-zinc-100 dark:border-zinc-800">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block mb-1">Catatan Musyrif</span>
                    <p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line">
                        {{ $murajaahRecord->notes ?: 'Tidak ada catatan khusus.' }}
                    </p>
                </div>

                <!-- WhatsApp Quick Action Box -->
                <div class="bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-emerald-950 dark:text-emerald-300 flex items-center gap-1.5">
                            <span>💬 Laporan WhatsApp Wali Santri</span>
                        </h4>
                        <p class="text-xs text-emerald-800 dark:text-emerald-400/90">
                            @php
                                $parentPhone = $murajaahRecord->student?->parents?->pluck('phone')->filter()->first();
                            @endphp
                            @if($parentPhone)
                                Terhubung ke nomor wali: <strong>{{ $parentPhone }}</strong>
                            @else
                                <span class="text-amber-700 dark:text-amber-400">⚠️ Nomor telepon wali belum terdaftar di profil. Anda dapat memilih kontak langsung di WhatsApp.</span>
                            @endif
                        </p>
                    </div>

                    <a href="{{ $murajaahRecord->whatsapp_share_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow transition duration-150 shrink-0 w-full sm:w-auto">
                        <span>Buka WhatsApp</span>
                        <span>→</span>
                    </a>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>