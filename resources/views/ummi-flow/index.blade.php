<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-zinc-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-clock class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                <span>Alur Pembelajaran Ummi</span>
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Timer per sesi untuk musyrif Kelas 10: bunyi pengingat 1 menit sebelum sesi habis, lalu bunyi, getar &amp; notifikasi saat waktunya pindah sesi.
            </p>
        </div>
    </x-slot>

    @php
        $entry = $meeting['entry'] ?? null;
        $total = array_sum(array_column($sessions, 'minutes'));
        // Isi tiap sesi dari target pertemuan hari ini.
        $hints = [
            'pembukaan' => "Salam, doa pembuka, presensi.",
            'murojaah' => $entry ? 'Drill: '.\App\Services\UmmiProsemService::rangeLabel($entry['drill']) : null,
            'ziyadah' => $entry ? 'Hafalan baru: '.\App\Services\UmmiProsemService::rangeLabel($entry['hafalan']) : null,
            'klasikal' => $entry && $entry['jilid'] ? $entry['jilid'].' · peraga hlm '.\App\Services\UmmiProsemService::pagesLabel($entry['peraga']).' · buku hlm '.\App\Services\UmmiProsemService::pagesLabel($entry['buku']) : ($entry ? 'Pengulangan / evaluasi' : null),
            'penutup' => 'Evaluasi singkat, doa penutup.',
        ];
        $months = \App\Http\Controllers\UmmiProsemController::MONTH_LABELS;
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('ummi-flow.index') }}" class="flex flex-wrap items-end gap-3">
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Kelas
                    <select name="class_room_id" onchange="this.form.submit()" class="mt-1 block rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @forelse ($classes as $option)
                            <option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}</option>
                        @empty
                            <option value="">Tidak ada kelas 10</option>
                        @endforelse
                    </select>
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 pb-2">{{ $today->locale('id')->translatedFormat('l, d F Y') }} · total {{ $total }} menit</p>
            </form>

            {{-- Target pertemuan hari ini (Prosem) --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
                @if ($entry)
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Target pertemuan hari ini · TM {{ $entry['no'] }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $months[$entry['month']] }} pekan {{ $entry['week'] }}{{ ($meeting['nth'] ?? 0) === 0 ? ' · hari ini bukan jadwal Ummi kelas ini' : '' }}</p>
                    </div>
                    <dl class="mt-3 grid sm:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                            <dt class="text-[11px] font-bold uppercase text-zinc-500 dark:text-zinc-400">Klasikal</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">{{ $hints['klasikal'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                            <dt class="text-[11px] font-bold uppercase text-zinc-500 dark:text-zinc-400">Ziyadah</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">{{ \App\Services\UmmiProsemService::rangeLabel($entry['hafalan']) }}</dd>
                        </div>
                        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                            <dt class="text-[11px] font-bold uppercase text-zinc-500 dark:text-zinc-400">Muraja'ah</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">{{ \App\Services\UmmiProsemService::rangeLabel($entry['drill']) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        Tidak ada tatap muka Prosem terjadwal pekan ini.
                        @if (! empty($meeting['next']))
                            Berikutnya: TM {{ $meeting['next']['no'] }} ({{ $months[$meeting['next']['month']] }} pekan {{ $meeting['next']['week'] }}).
                        @endif
                        <a href="{{ route('prosem-ummi.index') }}" class="font-semibold text-teal-700 dark:text-teal-400 hover:underline">Lihat Prosem</a>
                    </p>
                @endif
            </div>

            {{-- Timer --}}
            <div x-data="ummiFlowTimer(@js($sessions), @js($storageKey))" class="grid lg:grid-cols-5 gap-5">
                <div class="lg:col-span-3 rounded-2xl border shadow-sm p-6 text-center transition-colors"
                     :class="overtime ? 'bg-rose-50 border-rose-300 dark:bg-rose-500/10 dark:border-rose-500/40' : 'bg-white border-zinc-200 dark:bg-zinc-900 dark:border-zinc-800'">
                    <p class="text-xs font-bold uppercase tracking-widest text-zinc-500 dark:text-zinc-400"
                       x-text="finished ? 'Selesai' : ('Sesi ' + (state.index + 1) + ' dari ' + sessions.length + (current && current.group ? ' · ' + current.group : ''))"></p>
                    <h3 class="mt-1 text-2xl font-black text-zinc-900 dark:text-white" x-text="finished ? 'Pembelajaran selesai' : (current ? current.label : '')"></h3>
                    <p class="mt-4 font-black tabular-nums text-7xl sm:text-8xl"
                       :class="overtime ? 'text-rose-600 dark:text-rose-400 animate-pulse' : (remainingMs <= 60000 && started ? 'text-amber-500' : 'text-teal-700 dark:text-teal-400')"
                       x-text="finished ? '✓' : clock"></p>
                    <p class="mt-1 text-sm font-semibold text-rose-600 dark:text-rose-400" x-show="overtime && !finished">Lewat waktu — pindah ke sesi berikutnya</p>
                    <div class="mt-5 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        <div class="h-full transition-all" :class="overtime ? 'bg-rose-500' : 'bg-teal-500'" :style="'width: ' + (finished ? 100 : progress) + '%'"></div>
                    </div>
                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                        <button type="button" x-show="!running && !finished" @click="start()" class="rounded-xl bg-teal-600 hover:bg-teal-700 px-6 py-3 text-sm font-bold text-white" x-text="started ? 'Lanjutkan' : 'Mulai'"></button>
                        <button type="button" x-show="running" @click="pause()" class="rounded-xl bg-amber-500 hover:bg-amber-600 px-6 py-3 text-sm font-bold text-white">Jeda</button>
                        <button type="button" x-show="!finished" @click="next()" class="rounded-xl border border-zinc-300 dark:border-zinc-700 px-6 py-3 text-sm font-bold text-zinc-800 dark:text-zinc-100"
                                x-text="state.index >= sessions.length - 1 ? 'Selesai' : 'Sesi berikutnya →'"></button>
                        <button type="button" @click="reset()" class="rounded-xl px-4 py-3 text-sm font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200">Ulang</button>
                    </div>
                    <p class="mt-3 text-[11px] text-zinc-400 dark:text-zinc-500">Izinkan notifikasi & biarkan halaman terbuka agar pengingat berbunyi. Layar dijaga tetap menyala selama timer berjalan.</p>
                </div>

                <ol class="lg:col-span-2 space-y-2">
                    @foreach ($sessions as $i => $session)
                        <li class="rounded-xl border p-3 transition-colors"
                            :class="{
                                'border-teal-400 bg-teal-50 dark:bg-teal-500/10 dark:border-teal-500/50': sessionStatus({{ $i }}) === 'current',
                                'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 opacity-60': sessionStatus({{ $i }}) === 'done',
                                'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900': sessionStatus({{ $i }}) === 'next',
                            }">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-bold text-zinc-900 dark:text-white">
                                    <span x-show="sessionStatus({{ $i }}) === 'done'" class="text-emerald-600">✓</span>
                                    {{ $session['group'] ? $session['group'].' · ' : '' }}{{ $session['label'] }}
                                </p>
                                <span class="text-xs font-black text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ $session['minutes'] }} menit</span>
                            </div>
                            @if ($hints[$session['key']] ?? null)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hints[$session['key']] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($canConfigure)
                <details class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-bold text-zinc-800 dark:text-zinc-200">Atur durasi sesi</summary>
                    <form method="POST" action="{{ route('ummi-flow.settings') }}" class="mt-4 grid sm:grid-cols-5 gap-3 items-end">
                        @csrf
                        @foreach ($sessions as $session)
                            <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ $session['label'] }}
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="number" min="1" max="120" name="minutes[{{ $session['key'] }}]" value="{{ $session['minutes'] }}" class="w-20 rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                                    <span>menit</span>
                                </div>
                            </label>
                        @endforeach
                        <button type="submit" class="sm:col-span-5 justify-self-start rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2 text-xs font-bold text-white">Simpan durasi</button>
                    </form>
                </details>
            @endif
        </div>
    </div>
</x-app-layout>
