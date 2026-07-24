<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IMS — Platform Pelacakan Hafalan & Murajaah Qur'an Modern</title>

        <!-- Theme Initialization Script -->
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-50 text-zinc-800 dark:bg-[#09090b] dark:text-zinc-100 font-sans antialiased selection:bg-indigo-500 selection:text-white relative overflow-x-hidden min-h-screen transition-colors duration-200">
        <!-- Glow background blobs -->
        <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="glow-blob bg-indigo-600 w-[600px] h-[600px] -top-80 -left-60 opacity-5 dark:opacity-25 transition-opacity duration-300"></div>
            <div class="glow-blob bg-purple-600 w-[550px] h-[550px] top-[20%] -right-40 opacity-4 dark:opacity-20 transition-opacity duration-300"></div>
            <div class="glow-blob bg-emerald-600 w-[500px] h-[500px] top-[60%] left-[-200px] opacity-3 dark:opacity-15 transition-opacity duration-300"></div>
            <div class="glow-blob bg-indigo-50 w-[650px] h-[650px] bottom-[-200px] right-[-200px] opacity-10 dark:opacity-20 transition-opacity duration-300"></div>
        </div>

        <div class="relative z-10 flex flex-col min-h-screen" x-data="{ dark: document.documentElement.classList.contains('dark'), toggleTheme() { this.dark = !this.dark; if (this.dark) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); } } }">
            <!-- Navigation Header -->
            <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                <nav class="bg-white/80 dark:bg-zinc-900/60 border border-zinc-200/50 dark:border-white/5 shadow-sm dark:shadow-none backdrop-blur-md flex items-center justify-between px-6 py-3 rounded-2xl transition-all duration-200">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <svg class="h-7 w-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="font-bold text-xl tracking-tight text-zinc-850 dark:text-white">IMS</span>
                        </div>
                        
                        <!-- Theme Toggle Button -->
                        <button @click="toggleTheme()" class="p-1.5 rounded-lg bg-zinc-100 hover:bg-zinc-200/60 dark:bg-white/5 border border-zinc-200 dark:border-white/10 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-150 shadow-sm dark:shadow-none" title="Ubah Tema">
                            <svg x-show="dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                            <svg x-show="!dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-white dark:bg-white/5 border border-zinc-200 dark:border-white/10 text-zinc-800 dark:text-white px-4 py-2 text-sm font-semibold rounded-xl hover:bg-zinc-100 dark:hover:bg-white/10 transition-colors duration-150 shadow-sm">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors duration-150">
                                    Log in
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 border border-indigo-400/20 shadow-[0_4px_20px_rgba(13,148,136,0.25)] dark:shadow-[0_4px_20px_rgba(13,148,136,0.3)] transition-all duration-200 hover:scale-[1.02]">
                                        Mulai Sekarang
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </nav>
            </header>

            <!-- Main Content -->
            <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col gap-24 relative z-10">
                <!-- Hero & Mockup Split Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center min-h-[calc(100vh-140px)]">
                    <!-- Left Column: Hero Content -->
                    <div class="lg:col-span-5 flex flex-col items-start gap-6 text-left">
                        <!-- Premium Badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-500/20 backdrop-blur-md shadow-inner text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
                            </span>
                            Tahfidz & Murajaah Tracker
                        </div>

                        <!-- Main Heading -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-zinc-900 dark:text-white leading-none">
                            Platform Tahfidz<br>
                            <span class="font-serif italic text-gradient-purple-blue font-normal leading-tight">Modern & Terarah</span>
                        </h1>

                        <!-- Description -->
                        <p class="text-base text-zinc-650 dark:text-zinc-400 leading-relaxed max-w-md">
                            Sistem terintegrasi untuk pelacakan setoran hafalan, murajaah, adab, dan poin disiplin murid secara real-time.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-row items-center gap-4 mt-2 w-full sm:w-auto">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="px-6 py-3 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 border border-indigo-400/20 shadow-lg shadow-indigo-600/20 transition-all duration-200 hover:-translate-y-0.5">
                                    Masuk ke Dasbor
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="px-6 py-3 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 border border-indigo-400/20 shadow-lg shadow-indigo-600/20 transition-all duration-200 hover:-translate-y-0.5">
                                    Masuk Aplikasi
                                </a>
                                <a href="#features" class="bg-white/80 dark:bg-white/5 border border-zinc-200 dark:border-white/10 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10 px-6 py-3 text-sm font-semibold rounded-xl transition-all duration-150 shadow-sm hover:-translate-y-0.5">
                                    Pelajari Fitur
                                </a>
                            @endauth
                        </div>
                    </div>

                    <!-- Right Column: Mockup Showcase (Interactive App Preview) -->
                    <div class="lg:col-span-7 w-full flex justify-center">
                        <div class="w-full bg-white/80 dark:bg-zinc-900/45 rounded-3xl p-4 sm:p-5 border border-zinc-200 dark:border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.04)] dark:shadow-[0_30px_100px_rgba(0,0,0,0.6)] relative group overflow-hidden transition-all duration-300 hover:scale-[1.01] hover:shadow-[0_25px_60px_rgba(0,0,0,0.06)] dark:hover:shadow-[0_40px_120px_rgba(0,0,0,0.7)]">
                            <!-- Glass shine overlay -->
                            <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/5 to-white/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 ease-out pointer-events-none"></div>

                            <!-- Mockup Header -->
                            <div class="flex items-center justify-between pb-3 border-b border-zinc-200/50 dark:border-white/5 mb-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500/85"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-500/85"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-500/85"></span>
                                </div>
                                <div class="px-3 py-0.5 rounded bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/5 text-[10px] text-zinc-500 font-mono">
                                    demo.ims-system.com/dashboard/student
                                </div>
                                <div class="w-10"></div>
                            </div>

                            <!-- Mockup Body (Estetika UI Dashboard) -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-zinc-50 dark:bg-zinc-950/40 rounded-2xl p-4 min-h-[300px] border border-zinc-200/50 dark:border-white/5 transition-all duration-200">
                                <!-- Sidebar Mockup -->
                                <div class="flex flex-col gap-3 pr-2 border-r border-zinc-200 dark:border-white/5 md:block hidden">
                                    <div class="h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center px-3 gap-2 mb-4">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-300">Utama</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-6 rounded bg-zinc-200 dark:bg-white/5 w-4/5"></div>
                                        <div class="h-6 rounded bg-zinc-200 dark:bg-white/5 w-3/5"></div>
                                        <div class="h-6 rounded bg-zinc-200 dark:bg-white/5 w-2/3"></div>
                                    </div>
                                </div>

                                <!-- Main Content Mockup -->
                                <div class="md:col-span-3 flex flex-col gap-4">
                                    <!-- Cards row -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="bg-white dark:bg-white/5 border border-zinc-200 dark:border-white/5 p-3 rounded-xl shadow-sm">
                                            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Hafalan Baru</div>
                                            <div class="text-lg font-bold text-zinc-800 dark:text-white mt-1">28 Juz</div>
                                            <div class="text-[9px] text-emerald-600 dark:text-emerald-400 mt-1 font-medium">↑ +2 Halaman</div>
                                        </div>
                                        <div class="bg-white dark:bg-white/5 border border-zinc-200 dark:border-white/5 p-3 rounded-xl shadow-sm">
                                            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Murajaah</div>
                                            <div class="text-lg font-bold text-zinc-800 dark:text-white mt-1">15 Juz</div>
                                            <div class="text-[9px] text-indigo-600 dark:text-indigo-400 mt-1 font-medium">Status: Lancar</div>
                                        </div>
                                        <div class="bg-white dark:bg-white/5 border border-zinc-200 dark:border-white/5 p-3 rounded-xl shadow-sm">
                                            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Kehadiran</div>
                                            <div class="text-lg font-bold text-zinc-800 dark:text-white mt-1">98.5%</div>
                                            <div class="text-[9px] text-zinc-500 mt-1">Bulan Juni</div>
                                        </div>
                                    </div>

                                    <!-- Graph Mockup -->
                                    <div class="bg-white dark:bg-white/5 border border-zinc-200 dark:border-white/5 p-4 rounded-xl flex-1 flex flex-col gap-3 min-h-[140px] shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-zinc-800 dark:text-white">Statistik Progres Setoran</span>
                                            <span class="text-[10px] text-zinc-400">Minggu Ini</span>
                                        </div>
                                        <div class="flex-1 flex items-end justify-between gap-2 pt-4 px-2">
                                            <div class="bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 dark:border-indigo-500/30 rounded-t w-full h-[30%]"></div>
                                            <div class="bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 dark:border-indigo-500/30 rounded-t w-full h-[50%]"></div>
                                            <div class="bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 dark:border-indigo-500/30 rounded-t w-full h-[40%]"></div>
                                            <div class="bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 dark:border-indigo-500/30 rounded-t w-full h-[70%]"></div>
                                            <div class="bg-indigo-600 border border-indigo-400 rounded-t w-full h-[90%] shadow-[0_0_12px_rgba(13,148,136,0.3)] dark:shadow-[0_0_12px_rgba(13,148,136,0.5)]"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Grid Section -->
                <div id="features" class="w-full flex flex-col items-center gap-12 pt-10">
                    <div class="text-center flex flex-col gap-3 max-w-xl">
                        <h2 class="text-2xl sm:text-4xl font-bold text-zinc-900 dark:text-white">
                            Kenapa Memilih <span class="font-serif italic text-indigo-600 dark:text-indigo-400">IMS?</span>
                        </h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Fitur lengkap yang dirancang khusus untuk mempermudah ekosistem tahfidz Qur'an di pondok pesantren, sekolah, maupun rumah tahfidz.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                        <!-- Feature 1 -->
                        <div class="bg-white dark:bg-zinc-900/55 p-6 rounded-2xl border border-zinc-200 dark:border-white/5 flex flex-col gap-4 shadow-sm dark:shadow-none hover:shadow-md dark:hover:bg-zinc-900/75 transition-all duration-200 hover:-translate-y-1">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Input Cepat Setoran</h3>
                            <p class="text-sm text-zinc-650 dark:text-zinc-400 leading-relaxed">
                                Guru dapat merekam hasil hafalan (tambah baru) dan murajaah siswa dalam hitungan detik dengan form yang sangat optimal dan intuitif.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="bg-white dark:bg-zinc-900/55 p-6 rounded-2xl border border-zinc-200 dark:border-white/5 flex flex-col gap-4 shadow-sm dark:shadow-none hover:shadow-md dark:hover:bg-zinc-900/75 transition-all duration-200 hover:-translate-y-1">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-100 dark:border-purple-500/20 flex items-center justify-center text-purple-650 dark:text-purple-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Portal Wali Santri</h3>
                            <p class="text-sm text-zinc-650 dark:text-zinc-400 leading-relaxed">
                                Orangtua dapat masuk langsung untuk memantau kemajuan hafalan putra-putrinya secara langsung, melihat catatan ustaz, dan target yang belum tercapai.
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="bg-white dark:bg-zinc-900/55 p-6 rounded-2xl border border-zinc-200 dark:border-white/5 flex flex-col gap-4 shadow-sm dark:shadow-none hover:shadow-md dark:hover:bg-zinc-900/75 transition-all duration-200 hover:-translate-y-1">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Laporan Ekspor CSV/PDF</h3>
                            <p class="text-sm text-zinc-650 dark:text-zinc-400 leading-relaxed">
                                Hasilkan rekapitulasi data prestasi secara otomatis, yang siap diekspor ke format spreadsheet untuk keperluan evaluasi bulanan atau pembagian rapor tahfidz.
                            </p>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 border-t border-zinc-200 dark:border-white/5 text-center mt-20 relative z-10">
                <p class="text-xs text-zinc-500">
                    &copy; 2026 IMS (Integrated Management System). Dibuat dengan cinta untuk generasi penghafal Qur'an.
                </p>
            </footer>
        </div>
    </body>
</html>
