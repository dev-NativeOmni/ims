<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mushaf Al-Qur\'an Digital') }}
        </h2>
    </x-slot>

    @php
        $driveId = $config['google_drive_id'] ?? null;
        $driveLink = $config['google_drive_link'] ?? null;
        $isAdmin = auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin');
    @endphp

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6 w-full">

            <!-- Alert Messages with Micro-Animations -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl relative shadow-sm transition-all duration-300 animate-fade-in flex items-center gap-3" role="alert">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl relative shadow-sm transition-all duration-300 animate-fade-in flex items-center gap-3" role="alert">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="block sm:inline text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl relative shadow-sm transition-all duration-300 animate-fade-in" role="alert">
                    <div class="flex items-center gap-3 mb-2">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm font-semibold">Terdapat beberapa kesalahan:</span>
                    </div>
                    <ul class="list-disc pl-8 text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$driveId)
                <!-- ================= EMPTY STATE (NOT CONFIGURED YET) ================= -->
                @if ($isAdmin)
                    <!-- Admin Empty State with Set Config Form -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Instructions Box -->
                        <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 space-y-6">
                            <div>
                                <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider rounded-full">Panduan Konfigurasi</span>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-3 mb-2">Cara Menghubungkan Mushaf PDF dari Google Drive</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Agar seluruh pengguna (guru, santri, orangtua) dapat membaca Mushaf Al-Qur'an sekolah secara langsung tanpa terdownload otomatis oleh browser, Anda dapat menyimpannya di Google Drive dan membagikannya ke sistem ini.
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0 shadow-md">1</div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Unggah Berkas ke Google Drive</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                            Masuk ke Google Drive sekolah Anda, unggah file PDF Mushaf Al-Qur'an yang ingin ditampilkan di sistem.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0 shadow-md">2</div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Atur Izin Akses Menjadi Publik</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                            Klik kanan file di Drive &rarr; <strong>Bagikan (Share)</strong> &rarr; Ubah akses umum dari "Dibatasi (Restricted)" menjadi <strong>"Siapa saja yang memiliki link" (Anyone with the link)</strong> dengan peran sebagai <strong>Pelihat (Viewer)</strong>.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0 shadow-md">3</div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Salin Link dan Tempel</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                            Klik <strong>Salin link (Copy link)</strong>, lalu tempelkan link tersebut pada formulir konfigurasi di sebelah kanan. Sistem akan secara otomatis mendeteksi ID dokumen Anda.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Config Form Box -->
                        <div class="lg:col-span-5 bg-gradient-to-br from-indigo-900 to-indigo-950 text-white rounded-2xl shadow-xl p-8 border border-indigo-950 relative overflow-hidden flex flex-col justify-between min-h-[380px]">
                            <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
                            
                            <div class="relative z-10 space-y-4">
                                <div class="w-14 h-14 bg-white/10 rounded-xl flex items-center justify-center text-indigo-300 mb-2">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold">Sambungkan Mushaf</h3>
                                <p class="text-xs text-indigo-200 leading-relaxed">
                                    Tempelkan link berbagi Google Drive dari PDF Mushaf sekolah Anda untuk mengaktifkan viewer.
                                </p>
                            </div>

                            <form action="{{ route('quran.pdf.config') }}" method="POST" class="mt-6 space-y-4 relative z-10">
                                @csrf
                                <div class="space-y-2">
                                    <label for="drive_link" class="block text-xs font-semibold text-indigo-200 uppercase tracking-wider">Link Berbagi Google Drive</label>
                                    <input type="text" 
                                           id="drive_link" 
                                           name="drive_link" 
                                           placeholder="https://drive.google.com/file/d/.../view?usp=sharing" 
                                           class="block w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/40 focus:bg-white/20 focus:border-white/50 focus:ring-0 text-sm transition-all" 
                                           required />
                                </div>

                                <button type="submit" class="w-full inline-flex items-center justify-center px-5 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-emerald-500/20 transform hover:-translate-y-0.5 transition-all focus:outline-none">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Hubungkan Mushaf Digital
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- Non-Admin Empty State -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 border border-gray-100 dark:border-gray-700 text-center max-w-xl mx-auto my-12">
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-950/50 rounded-full flex items-center justify-center mx-auto mb-6 text-indigo-500">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Mushaf Al-Qur'an Belum Tersedia</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto leading-relaxed">
                            Berkas Mushaf Al-Qur'an digital belum dihubungkan oleh administrator sekolah. Silakan hubungi admin sekolah Anda untuk mengaktifkan fitur ini.
                        </p>
                    </div>
                @endif
            @else
                <!-- ================= FULL STATE (CONFIGURED) ================= -->
                @if ($isAdmin)
                    <!-- Admin Edit Bar with Accordion -->
                    <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-300">
                        <button @click="open = !open" class="w-full flex items-center justify-between p-5 text-left focus:outline-none">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white">Pengaturan Mushaf (Administrator)</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Mushaf saat ini aktif. Klik untuk melihat detail link atau menggantinya.</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse x-cloak class="border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-5 space-y-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-1">Link Google Drive Aktif</span>
                                <a href="{{ $driveLink }}" target="_blank" class="text-xs font-mono text-indigo-600 dark:text-indigo-400 hover:underline break-all flex items-center gap-1.5">
                                    {{ $driveLink }}
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <div class="mt-2 text-[10px] text-gray-400">File ID: <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ $driveId }}</span></div>
                            </div>

                            <form action="{{ route('quran.pdf.config') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                                @csrf
                                <div class="flex-grow space-y-1">
                                    <label for="drive_link_edit" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Hubungkan Link Google Drive Baru</label>
                                    <input type="text" 
                                           id="drive_link_edit" 
                                           name="drive_link" 
                                           value="{{ $driveLink }}"
                                           placeholder="https://drive.google.com/file/d/.../view?usp=sharing" 
                                           class="block w-full px-4 py-2.5 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:border-indigo-500 focus:ring-indigo-500 text-gray-900 dark:text-white" 
                                           required />
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-gray-900 text-sm font-bold rounded-xl shadow-md transition-all shrink-0">
                                    Ganti Link Mushaf
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- PDF Viewer Card (Standard User Interface) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300">
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-red-400 shadow-sm animate-pulse"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400 shadow-sm animate-pulse delay-75"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-400 shadow-sm animate-pulse delay-150"></div>
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 pl-2">Mushaf Al-Qur'an Digital</span>
                        </div>
                        <div>
                            <a href="{{ $driveLink }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900/80 text-xs font-semibold rounded-xl text-indigo-700 dark:text-indigo-300 transition-all shadow-sm border border-indigo-100 dark:border-indigo-900">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Buka di Google Drive
                            </a>
                        </div>
                    </div>

                    <!-- Responsive Iframe Wrapper with elegant loading backdrop -->
                    <div class="relative w-full bg-gray-150 dark:bg-gray-900 shadow-inner rounded-xl overflow-hidden" style="height: 82vh; min-height: 750px;">
                        <iframe src="https://drive.google.com/file/d/{{ $driveId }}/preview" 
                                class="w-full h-full border-0" 
                                style="height: 82vh; min-height: 750px;"
                                allow="autoplay"
                                loading="lazy"></iframe>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Styles for basic page animation -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
