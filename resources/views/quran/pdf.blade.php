<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mushaf Al-Qur\'an Digital') }}
        </h2>
    </x-slot>

    @php
        $localPdf = public_path('pdf/quran.pdf');
        $hasLocalPdf = file_exists($localPdf);
        $pdfUrl = $hasLocalPdf ? asset('pdf/quran.pdf') : 'https://ia800903.us.archive.org/27/items/mushaf-madinah-pdf/mushaf-madinah.pdf';
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
            
            @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl shadow-lg p-6 relative overflow-hidden transition-all duration-300 hover:shadow-xl">
                    <div class="absolute right-0 top-0 -mt-6 -mr-6 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                    <div class="flex items-start space-x-4">
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="space-y-1 flex-1">
                            <h4 class="font-bold text-lg">Informasi Pengaturan Mushaf (Administrator)</h4>
                            <p class="text-sm opacity-90 leading-relaxed">
                                Saat ini sistem menggunakan link CDN Mushaf Al-Qur'an Madinah default. Anda dapat menggantinya dengan Mushaf standar sekolah Anda sendiri dengan cara mengunggah file PDF Anda ke direktori server:
                            </p>
                            <code class="mt-2 inline-block bg-white bg-opacity-10 text-xs font-mono px-3 py-1.5 rounded border border-white border-opacity-20">
                                public/pdf/quran.pdf
                            </code>
                            @if($hasLocalPdf)
                                <div class="mt-2 flex items-center text-xs text-green-200 font-semibold">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    File lokal terdeteksi aktif.
                                </div>
                            @else
                                <div class="mt-2 flex items-center text-xs text-yellow-200 font-semibold">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Menggunakan file online (fallback).
                                </div>
                            @endif

                            <form action="{{ route('quran.pdf.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4 p-4 bg-white bg-opacity-10 rounded-lg border border-white border-opacity-10 space-y-3">
                                @csrf
                                <label class="block text-xs font-semibold uppercase tracking-wider opacity-85">Unggah Mushaf PDF Baru</label>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                    <input type="file" name="pdf_file" accept=".pdf" class="block w-full text-xs text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-white file:text-indigo-700 hover:file:bg-indigo-50 transition-all cursor-pointer" required />
                                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-gray-900 text-xs font-bold rounded-md shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 shrink-0">
                                        Unggah & Simpan
                                    </button>
                                </div>
                                <p class="text-[10px] opacity-75">Format berkas harus PDF, maksimal ukuran 50MB.</p>
                            </form>
                        </div>
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
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Mushaf Reader</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ $pdfUrl }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-semibold rounded-lg text-gray-700 dark:text-gray-300 transition-all">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Buka di Tab Baru
                        </a>
                    </div>
                </div>

                <div class="relative w-full h-[80vh] bg-gray-900 flex items-center justify-center">
                    <!-- Standard Iframe to render PDF -->
                    <iframe src="{{ $pdfUrl }}" class="w-full h-full border-0" allow="autoplay"></iframe>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
