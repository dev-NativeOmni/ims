<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Pengaturan Penilaian Tahfizh
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Atur bobot nilai akhir tahfizh yang tampil di rapor: gabungan ketuntasan target hafalan dan ujian tahfizh.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('settings.tahfizh-scoring.reset') }}" onsubmit="return confirm('Reset pengaturan penilaian tahfizh ke standar default (50/50, nilai belum tuntas 40, predikat Mumtaz/Jayyid Jiddan/Jayyid/Maqbul)?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-750 transition cursor-pointer shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset ke Standar
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-950/40 dark:border-red-800/60 dark:text-red-300">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.tahfizh-scoring.update') }}" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm p-6 space-y-5">
                    <div class="grid grid-cols-1 xs:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Bobot Ketuntasan Target</label>
                            <input type="number" name="target_weight" min="0" max="100" required
                                   value="{{ old('target_weight', $config['target_weight']) }}"
                                   class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold text-center" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Bobot Ujian Tahfizh</label>
                            <input type="number" name="exam_weight" min="0" max="100" required
                                   value="{{ old('exam_weight', $config['exam_weight']) }}"
                                   class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold text-center" />
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Kedua bobot ini harus dijumlahkan sama dengan 100.</p>

                    <div class="space-y-1.5 pt-2 border-t border-gray-100 dark:border-zinc-800">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Nilai Target Belum Tuntas</label>
                        <input type="number" name="target_incomplete_score" min="0" required
                               value="{{ old('target_incomplete_score', $config['target_incomplete_score']) }}"
                               class="block w-full sm:w-40 rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold text-center" />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Poin yang tetap didapat murid dari komponen target walaupun targetnya belum ditandai selesai.
                        </p>
                    </div>
                </div>

                {{-- Predikat nilai Tahfizh di rapor (Setting::tahfizhPredicate()), mis. "87 / Jayyid Jiddan". --}}
                @php $predicates = old('predicates', $config['predicates']); @endphp
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm p-6 space-y-4"
                     x-data="{ mins: @js(collect($predicates)->pluck('min')->map(fn ($min) => (int) $min)->values()) }">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Predikat Nilai Tahfizh</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Tercetak di kolom Nilai rapor setelah angkanya, mis. <span class="font-semibold">87 / Jayyid Jiddan</span>. Isi nilai minimal tiap predikat dari yang tertinggi.</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($predicates as $i => $predicate)
                            @php
                                $isLast = $loop->last;
                                // Rentang nilai tiap predikat; x-text ikut berubah saat nilai minimal diketik.
                                [$rangeText, $rangeExpression] = match (true) {
                                    $isLast => ['di bawah '.$predicates[$i - 1]['min'], "'di bawah ' + mins[".($i - 1).']'],
                                    $i === 0 => [$predicate['min'].' – 100', "mins[0] + ' – 100'"],
                                    default => [$predicate['min'].' – '.((int) $predicates[$i - 1]['min'] - 1), "mins[{$i}] + ' – ' + (mins[".($i - 1).'] - 1)'],
                                };
                            @endphp
                            <div class="grid grid-cols-[1fr_5.5rem] sm:grid-cols-[8rem_5.5rem_1fr] items-center gap-x-3 gap-y-2 py-2.5">
                                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300 tabular-nums" x-text="{{ $rangeExpression }}">{{ $rangeText }}</span>
                                @if ($isLast)
                                    <span class="text-xs text-center text-zinc-400 dark:text-zinc-500">otomatis</span>
                                @else
                                    <input type="number" name="predicates[{{ $i }}][min]" min="1" max="100" required x-model.number="mins[{{ $i }}]"
                                           value="{{ $predicate['min'] }}" aria-label="Nilai minimal predikat {{ $i + 1 }}"
                                           class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold text-center" />
                                @endif
                                <input type="text" name="predicates[{{ $i }}][label]" maxlength="50" required value="{{ $predicate['label'] }}" aria-label="Nama predikat {{ $i + 1 }}"
                                       class="col-span-2 sm:col-span-1 w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Formula Preview --}}
                <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 rounded-xl px-6 py-4 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                        <x-heroicon-o-calculator class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Formula Nilai Akhir Tahfizh</span>
                    </h4>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                        Nilai akhir tahfizh yang tampil di rapor dihitung dari:
                    </p>
                    <div class="grid grid-cols-1 xs:grid-cols-2 gap-3 text-center text-xs">
                        <div class="rounded-lg p-3 bg-white dark:bg-zinc-900 border border-indigo-100 dark:border-indigo-900/40">
                            <div class="font-bold text-zinc-700 dark:text-zinc-300">Ketuntasan Target Terbaru</div>
                            <div class="mt-1 text-zinc-500 dark:text-zinc-400">Tuntas &rarr; bobot penuh · Belum &rarr; nilai default</div>
                        </div>
                        <div class="rounded-lg p-3 bg-white dark:bg-zinc-900 border border-indigo-100 dark:border-indigo-900/40">
                            <div class="font-bold text-zinc-700 dark:text-zinc-300">Ujian Tahfizh Terbaru</div>
                            <div class="mt-1 text-zinc-500 dark:text-zinc-400">Diisi guru langsung sesuai bobot ujian</div>
                        </div>
                    </div>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 italic">
                        Contoh dengan pengaturan saat ini: murid yang belum tuntas target mendapat {{ $config['target_incomplete_score'] }} dari {{ $config['target_weight'] }} poin, ditambah nilai ujian tahfizh terbarunya (maksimal {{ $config['exam_weight'] }} poin).
                    </p>
                </div>

                {{-- Tombol Aksi --}}
                <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all duration-150">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
