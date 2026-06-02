<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mushaf Al-Qur\'an Digital') }}
        </h2>
    </x-slot>

    @php
        $localPdf = public_path('pdf/quran.pdf');
        $hasLocalPdf = file_exists($localPdf);
        $pdfUrl = $hasLocalPdf ? asset('pdf/quran.pdf') : null;
        $isAdmin = auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Session Messages -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$hasLocalPdf)
                <!-- EMPTY STATE (Mushaf not uploaded yet) -->
                @if ($isAdmin)
                    <!-- Admin Empty State with Upload Form -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 text-center max-w-2xl mx-auto my-12 transition-all duration-300 hover:shadow-2xl">
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6 text-indigo-500">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Mushaf Al-Qur'an Belum Tersedia</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto leading-relaxed">
                            Silakan unggah berkas Mushaf PDF Al-Qur'an sekolah Anda melalui formulir di bawah ini untuk mengaktifkan fitur membaca Mushaf secara lokal bagi seluruh guru, santri, dan orangtua.
                        </p>
                        
                        <form action="{{ route('quran.pdf.upload') }}" method="POST" enctype="multipart/form-data" class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600 space-y-4 max-w-md mx-auto">
                            @csrf
                            <div class="text-left">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">Pilih File Mushaf PDF</label>
                                <input type="file" name="pdf_file" accept=".pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer" required />
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Unggah & Aktifkan Mushaf
                            </button>
                            <p class="text-[10px] text-gray-400">Format berkas harus PDF, maksimal ukuran 50MB.</p>
                        </form>
                    </div>
                @else
                    <!-- Non-Admin Empty State -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 text-center max-w-2xl mx-auto my-12">
                        <div class="w-20 h-20 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-400">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Mushaf Al-Qur'an Belum Tersedia</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto leading-relaxed">
                            Berkas Mushaf Al-Qur'an digital belum diunggah oleh administrator sekolah. Silakan hubungi admin sekolah Anda untuk mengaktifkan fitur ini.
                        </p>
                    </div>
                @endif
            @else
                <!-- FULL STATE (Mushaf is uploaded) -->
                @if ($isAdmin)
                    <!-- Admin Edit/Swap Box -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl shadow-lg p-6 relative overflow-hidden transition-all duration-300 hover:shadow-xl">
                        <div class="absolute right-0 top-0 -mt-6 -mr-6 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                            <div class="space-y-1">
                                <h4 class="font-bold text-lg">Pengaturan Mushaf (Administrator)</h4>
                                <p class="text-sm opacity-90">
                                    Mushaf sekolah Anda saat ini aktif dan dapat dibaca secara lokal oleh semua pengguna. Anda dapat menggantinya dengan file PDF yang lain kapan saja.
                                </p>
                                <div class="mt-2 flex items-center text-xs text-green-200 font-semibold">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    File lokal aktif di: <span class="font-mono text-white bg-white bg-opacity-10 px-1.5 py-0.5 rounded ml-1">public/pdf/quran.pdf</span>
                                </div>
                            </div>
                            
                            <form action="{{ route('quran.pdf.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white bg-opacity-10 p-3 rounded-lg border border-white border-opacity-10 shrink-0">
                                @csrf
                                <input type="file" name="pdf_file" accept=".pdf" class="block w-full text-xs text-white file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-white file:text-indigo-700 hover:file:bg-indigo-50 transition-all cursor-pointer" required />
                                <button type="submit" class="inline-flex items-center justify-center px-4 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-gray-900 text-xs font-bold rounded-md shadow-sm transition-all shrink-0">
                                    Ganti PDF
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- PDF Viewer Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Mushaf Reader (Lokal)</span>
                        </div>
                        <div>
                            <a href="{{ $pdfUrl }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-semibold rounded-lg text-gray-700 dark:text-gray-300 transition-all">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Buka di Tab Baru
                            </a>
                        </div>
                    </div>

                    <div class="relative w-full h-[80vh] bg-gray-900 flex items-center justify-center">
                        <object data="{{ $pdfUrl }}" type="application/pdf" class="w-full h-full border-0">
                            <iframe src="{{ $pdfUrl }}" class="w-full h-full border-0" allow="autoplay"></iframe>
                        </object>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
