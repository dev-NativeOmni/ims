<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Pengaturan Sistem
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Kustomisasi tampilan branding instansi dan halaman login.
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

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Kustomisasi Branding & Login Page
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                        Unggah logo, background, dan ubah nama instansi yang tampil pada halaman login dan navigasi utama.
                    </p>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf

                    <!-- Nama Instansi -->
                    <div class="space-y-2">
                        <label for="nama_instansi" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Nama Instansi / Sekolah
                        </label>
                        <input
                            type="text"
                            name="nama_instansi"
                            id="nama_instansi"
                            value="{{ old('nama_instansi', $nama_instansi) }}"
                            placeholder="Contoh: Pondok Pesantren Al-Hikmah"
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <p class="text-xs text-gray-500 dark:text-zinc-500">
                            Nama instansi ini akan ditampilkan di bawah teks utama pada halaman login dan di navigasi header.
                        </p>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Custom Logo -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Logo Instansi
                            </label>
                            <p class="text-xs text-gray-500 dark:text-zinc-500">
                                Rekomendasi gambar PNG transparan beresolusi persegi (misal: 512x512px). Maksimal 2MB.
                            </p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @if ($logo)
                                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <img src="{{ asset('storage/' . $logo) }}" alt="Logo Instansi" class="w-16 h-16 object-contain rounded-lg bg-white border p-1" />
                                    <div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-zinc-300">Logo Custom Aktif</p>
                                        <label class="inline-flex items-center mt-1 text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus & kembali ke Logo Default
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800 text-xs text-gray-500 dark:text-zinc-400 flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white dark:bg-[#09090b]/40 border dark:border-zinc-800 flex items-center justify-center text-gray-455 font-bold">
                                        DEF
                                    </div>
                                    <span>Menggunakan logo default HafizPlus (SVG).</span>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="logo"
                                accept="image/*"
                                class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Custom Background -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Background Login
                            </label>
                            <p class="text-xs text-gray-500 dark:text-zinc-500">
                                Gambar background halaman login. Rekomendasi gambar horizontal (misal: 1920x1080px). Maksimal 5MB.
                            </p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @if ($login_bg)
                                <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <div class="w-full h-32 rounded-lg overflow-hidden border dark:border-zinc-800 bg-gray-200">
                                        <img src="{{ asset('storage/' . $login_bg) }}" alt="Background Login" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-900 dark:text-zinc-300">Background Custom Aktif</span>
                                        <label class="inline-flex items-center text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_login_bg" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus & kembali ke Background Default
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800 text-xs text-gray-500 dark:text-zinc-400 flex items-center gap-3">
                                    <div class="w-16 h-10 rounded bg-gradient-to-r from-slate-900 to-indigo-950 border dark:border-zinc-800"></div>
                                    <span>Menggunakan background default (Slate & Indigo Gradient).</span>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="login_bg"
                                accept="image/*"
                                class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
