<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-flag class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span>Target Triwulan</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Isi target surah &amp; ayat tiap bulan untuk murid yang diampu. Deadline tiap bulan = hari aktif terakhir di bulan itu (Senin–Jumat, bukan libur).
                Target baris = pertemuan aktif × baris per level (per bulan &amp; triwulan). Capaian = baris setoran lulus.
                Tuntas bila capaian baris ≥ target baris. Target surah &amp; ayat dari guru menjadi arah hafalan.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('hafalan-targets.partials.period-tabs')

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                    <p class="font-bold">Target belum disimpan:</p>
                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('hafalan-targets.partials.target-grid', ['grid' => $grid])
        </div>
    </div>


</x-app-layout>
