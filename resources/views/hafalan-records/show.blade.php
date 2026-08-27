<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                    Detail Setoran Hafalan
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $hafalanRecord->student?->name }} • {{ $hafalanRecord->submitted_at?->format('d M Y') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $hafalanRecord->whatsapp_share_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150">
                    <span>💬 Kirim Laporan via WhatsApp</span>
                </a>

                <a href="{{ route('hafalan-records.edit', $hafalanRecord) }}"
                   class="btn-action-edit inline-flex items-center gap-1 px-3.5 py-2">
                    ✏️ Edit
                </a>

                <a href="{{ route('hafalan-records.index') }}"
                   class="btn-action-detail inline-flex items-center gap-1 px-3.5 py-2">
                    ← Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Card Utama Setoran -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-2xl p-6 space-y-6">
                
                <!-- Header Card: Status & Nilai -->
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block">Status Kelulusan</span>
                        <span class="inline-block mt-1 px-3 py-1 rounded-xl text-xs font-bold
                            {{ $hafalanRecord->status === 'passed' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : '' }}
                            {{ $hafalanRecord->status === 'repeat' ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : '' }}
                            {{ $hafalanRecord->status === 'needs_improvement' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : '' }}
                        ">
                            {{ $hafalanRecord->status_label }}
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block">Predikat & Nilai</span>
                        <div class="mt-1 flex items-baseline gap-1.5 justify-end">
                            <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ $hafalanRecord->score_letter ?? '-' }}</span>
                            @if($hafalanRecord->score !== null)
                                <span class="text-xs font-bold text-zinc-500">({{ number_format((float) $hafalanRecord->score, 1) }})</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Grid Data Setoran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 text-sm">
                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Murid</span>
                        <p class="font-bold text-zinc-900 dark:text-white mt-1">{{ $hafalanRecord->student?->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $hafalanRecord->student?->classRoom?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Guru / Musyrif</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">{{ $hafalanRecord->teacher?->user?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Tanggal Setoran</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">{{ $hafalanRecord->submitted_at?->translatedFormat('l, j F Y') }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Surah</span>
                        <p class="font-bold text-zinc-900 dark:text-white mt-1">
                            {{ $hafalanRecord->surah?->number }}. {{ $hafalanRecord->surah?->name_latin }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Rentang Ayat</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">
                            Ayat {{ $hafalanRecord->ayah_start }} - {{ $hafalanRecord->ayah_end }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Capaian Baris & Jenis</span>
                        <p class="font-semibold text-zinc-900 dark:text-white mt-1">
                            <strong>{{ $hafalanRecord->lines_count }}</strong> Baris ({{ $hafalanRecord->submission_type_label }})
                        </p>
                    </div>
                </div>

                <!-- Catatan Musyrif -->
                <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-4 border border-zinc-100 dark:border-zinc-800">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block mb-1">Catatan Musyrif / Evaluasi</span>
                    <p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line">
                        {{ $hafalanRecord->notes ?: 'Tidak ada catatan khusus.' }}
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
                                $parentPhone = $hafalanRecord->student?->parents?->pluck('phone')->filter()->first();
                            @endphp
                            @if($parentPhone)
                                Terhubung ke nomor wali: <strong>{{ $parentPhone }}</strong>
                            @else
                                <span class="text-amber-700 dark:text-amber-400">⚠️ Nomor telepon wali belum terdaftar di profil. Anda dapat memilih kontak langsung di WhatsApp.</span>
                            @endif
                        </p>
                    </div>

                    <a href="{{ $hafalanRecord->whatsapp_share_url }}"
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