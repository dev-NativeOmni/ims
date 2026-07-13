<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Pengaturan Kuisioner Adab
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Sesuaikan teks kategori, deskripsi, dan 15 butir pertanyaan kuisioner perkembangan adab santri.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <form method="POST" action="{{ route('settings.adab.update') }}" class="space-y-6">
                @csrf

                @foreach ($categories as $catIdx => $category)
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                        <!-- Header Kartu Kategori -->
                        <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Nama Kategori</label>
                                    <input 
                                        type="text" 
                                        name="categories[{{ $catIdx }}][title]" 
                                        value="{{ old("categories.{$catIdx}.title", $category['title']) }}"
                                        class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold"
                                        required
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Deskripsi Kategori</label>
                                    <input 
                                        type="text" 
                                        name="categories[{{ $catIdx }}][desc]" 
                                        value="{{ old("categories.{$catIdx}.desc", $category['desc']) }}"
                                        class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Pertanyaan di dalam Kategori -->
                        <div class="p-6 space-y-4">
                            <h4 class="text-xs font-bold text-gray-400 dark:text-zinc-550 uppercase tracking-widest border-b pb-2">Butir Pertanyaan (Evaluasi Harian)</h4>
                            
                            @php
                                $startQ = ($catIdx * 5) + 1;
                                $endQ = $startQ + 4;
                            @endphp

                            <div class="space-y-4">
                                @for ($q = $startQ; $q <= $endQ; $q++)
                                    <div class="flex items-start gap-4">
                                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-xs font-bold text-indigo-600 dark:text-indigo-400 shrink-0 mt-1">
                                            {{ $q }}
                                        </span>
                                        <div class="flex-1">
                                            <input 
                                                type="text" 
                                                name="categories[{{ $catIdx }}][questions][q{{ $q }}]" 
                                                value="{{ old("categories.{$catIdx}.questions.q{$q}", $category['questions']["q{$q}"] ?? '') }}"
                                                placeholder="Ketik pertanyaan untuk nomor {{ $q }}..."
                                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                required
                                            />
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Tombol Aksi Simpan / Batal -->
                <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                    <a href="{{ route('adab.index') }}" class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all duration-150">
                        Simpan Semua Pertanyaan
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
