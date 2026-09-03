<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
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

        <style>
            /* Bulletproof Monolith Layout & Mobile First Architecture */
            .monolith-shell {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.25rem;
                align-items: start;
                width: 100%;
            }
            @media (min-width: 1024px) {
                .monolith-shell {
                    grid-template-columns: 290px minmax(0, 1fr);
                    gap: 1.5rem;
                }
            }
            .monolith-top-cards {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }
            @media (min-width: 1280px) {
                .monolith-top-cards {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 1rem;
                }
            }
            .monolith-bento-split {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
            @media (min-width: 1024px) {
                .monolith-bento-split {
                    grid-template-columns: minmax(0, 1.85fr) minmax(0, 1fr);
                    gap: 1.5rem;
                }
            }
            .monolith-bottom-badges {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }
            @media (min-width: 640px) {
                .monolith-bottom-badges {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 1rem;
                }
            }
            .monolith-glass-panel {
                background: rgba(255, 255, 255, 0.45);
                backdrop-filter: blur(24px);
                -webkit-backdrop-filter: blur(24px);
                border: 1px solid rgba(255, 255, 255, 0.75);
                border-radius: 1.75rem;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04);
            }
            @media (min-width: 640px) {
                .monolith-glass-panel {
                    border-radius: 2rem;
                }
            }
            .dark .monolith-glass-panel {
                background: rgba(24, 24, 27, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            .monolith-card-sm {
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.85);
                border-radius: 1.25rem;
                padding: 1rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            }
            @media (min-width: 640px) {
                .monolith-card-sm {
                    border-radius: 1.5rem;
                    padding: 1.25rem;
                }
            }
            .dark .monolith-card-sm {
                background: rgba(24, 24, 27, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        </style>
    </head>
    <body class="bg-[#09090b] text-zinc-100 font-sans antialiased selection:bg-teal-500 selection:text-white relative overflow-x-hidden min-h-screen transition-colors duration-500">

        <!-- Full-screen Preloader / Intro Logo Reveal -->
        <div x-data="{ loading: true, logoVisible: false }" 
             x-init="setTimeout(() => logoVisible = true, 100); setTimeout(() => loading = false, 2000)"
             x-show="loading"
             x-transition:leave="transition ease-in-out duration-1000"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-105 pointer-events-none"
             class="fixed inset-0 bg-[#09090b] z-[9999] flex flex-col items-center justify-center overflow-hidden">
            <!-- Glowing Grid background in preloader -->
            <div class="absolute inset-0 bg-grid-pattern opacity-10 pointer-events-none"></div>
            
            <!-- Outer Glowing Ambient Light -->
            <div class="absolute w-[600px] h-[600px] bg-teal-500/10 rounded-full blur-[120px] animate-pulse"></div>
            
            <!-- Large Centered Ring & Logo Container -->
            <div class="relative z-10 flex flex-col items-center gap-8">
                <!-- Glowing Ring -->
                <div :class="logoVisible ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
                     class="w-64 h-64 sm:w-80 sm:h-80 rounded-full border border-teal-500/35 flex items-center justify-center shadow-[0_0_80px_rgba(13,148,136,0.3)] transition-all duration-1000 ease-out p-0 overflow-hidden">
                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="w-full h-full object-cover">
                </div>
                
                <!-- Large Text Reveal -->
                <span :class="logoVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                      class="font-black text-3xl sm:text-5xl tracking-widest text-white uppercase transition-all duration-1000 delay-300 ease-out text-center">
                    Al Azhar <span class="text-amber-500">7</span>
                </span>
            </div>
        </div>

        <!-- Root Scroll Container with Alpine ScrollLayout -->
        <div x-data="scrollLayout" class="relative z-10 flex flex-col min-h-screen">
            
            <!-- Sticky Floating Header Navbar (When Scrolled) -->
            <header x-show="isScrolled" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-4"
                    class="fixed top-0 inset-x-0 z-50 bg-white/95 dark:bg-zinc-900/95 border-b border-zinc-200 dark:border-white/10 shadow-md py-2.5 backdrop-blur-md">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                    <!-- Brand Logo -->
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 min-w-[36px] min-h-[36px] max-w-[36px] max-h-[36px] rounded-full bg-white flex items-center justify-center p-1 shadow-sm border border-zinc-200 overflow-hidden shrink-0" style="width: 36px; height: 36px;">
                            <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="w-full h-full object-contain" style="width: 100%; height: 100%;">
                        </div>
                        <div class="flex flex-col">
                            <span class="font-extrabold text-sm tracking-wider text-zinc-900 dark:text-white uppercase leading-none">Al Azhar <span class="text-amber-500">7</span></span>
                            <span class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold tracking-wider uppercase mt-0.5">Integrated Management System</span>
                        </div>
                    </div>

                    <!-- Desktop Pill Menu Navigation -->
                    <nav class="hidden md:flex items-center gap-1 p-1 rounded-full bg-zinc-100 dark:bg-black/50 border border-zinc-200/80 dark:border-white/10 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        <a href="#fitur" class="px-3.5 py-1.5 rounded-full hover:text-zinc-950 dark:hover:text-white hover:bg-white dark:hover:bg-white/10 transition-all duration-150">Fitur Utama</a>
                        <a href="#keunggulan" class="px-3.5 py-1.5 rounded-full hover:text-zinc-950 dark:hover:text-white hover:bg-white dark:hover:bg-white/10 transition-all duration-150">Keunggulan</a>
                        <a href="#sebaran" class="px-3.5 py-1.5 rounded-full hover:text-zinc-950 dark:hover:text-white hover:bg-white dark:hover:bg-white/10 transition-all duration-150">Konektivitas</a>
                        <a href="#simulator" class="px-3.5 py-1.5 rounded-full hover:text-zinc-950 dark:hover:text-white hover:bg-white dark:hover:bg-white/10 transition-all duration-150">Demo Interaktif</a>
                        <a href="#faq" class="px-3.5 py-1.5 rounded-full hover:text-zinc-950 dark:hover:text-white hover:bg-white dark:hover:bg-white/10 transition-all duration-150">Tanya Jawab</a>
                    </nav>

                    <!-- Auth Actions -->
                    <div class="flex items-center gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-5 py-2 text-xs uppercase tracking-wider rounded-full transition-all duration-200 shadow-sm hover:scale-105 active:scale-95">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-5 py-2 text-xs uppercase tracking-wider rounded-full transition-all duration-200 shadow-sm hover:scale-105 active:scale-95">
                                    Masuk
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </header>

            <!-- ========================================================================= -->
            <!-- SECTION 1: MONOLITH SOFT-GLASSMORPHISM CLAY DASHBOARD SHOWCASE           -->
            <!-- ========================================================================= -->
            <section class="w-full relative min-h-screen py-6 sm:py-10 px-3 sm:px-6 lg:px-8 flex flex-col justify-center">
                
                <!-- Fluid Ambient Pastel Mesh Background Glows -->
                <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
                    <div class="absolute -top-40 -left-40 w-[650px] h-[650px] bg-emerald-300/35 dark:bg-emerald-600/15 rounded-full blur-[150px]"></div>
                    <div class="absolute top-1/4 -right-40 w-[650px] h-[650px] bg-cyan-300/35 dark:bg-cyan-600/15 rounded-full blur-[150px]"></div>
                    <div class="absolute -bottom-40 right-1/4 w-[700px] h-[700px] bg-amber-300/35 dark:bg-amber-600/15 rounded-full blur-[160px]"></div>
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/60 via-teal-50/40 to-amber-50/40 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 backdrop-blur-[2px]"></div>
                </div>

                <div class="max-w-[1400px] mx-auto w-full relative z-10">
                    
                    <!-- Mobile Top Brand Bar (Visible only on < lg screens) -->
                    <div class="lg:hidden flex items-center justify-between p-3.5 bg-white/65 dark:bg-zinc-900/65 backdrop-blur-2xl rounded-2xl border border-white/80 dark:border-white/10 shadow-sm mb-4 select-none">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 min-w-[32px] min-h-[32px] max-w-[32px] max-h-[32px] rounded-xl bg-white flex items-center justify-center p-1 shadow-sm border border-white/60 overflow-hidden shrink-0" style="width: 32px; height: 32px;">
                                <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMAIA 7" class="w-full h-full object-contain" style="width: 100%; height: 100%;">
                            </div>
                            <div class="flex flex-col">
                                <span class="font-extrabold text-xs sm:text-sm tracking-tight text-zinc-900 dark:text-white uppercase leading-none">Al Azhar <span class="text-amber-500">7</span></span>
                                <span class="text-[8px] sm:text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold tracking-wider uppercase mt-0.5">Integrated Management System</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="#fitur" class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-bold text-[10px] border border-white/60 shadow-xs">
                                Fitur
                            </a>
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-3.5 py-1.5 text-[10px] uppercase tracking-wider rounded-xl shadow-sm">
                                        Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-3.5 py-1.5 text-[10px] uppercase tracking-wider rounded-xl shadow-sm">
                                        Masuk
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>

                    <!-- Main Grid: Monolith Frosted Glass Shell -->
                    <div class="monolith-shell">
                        
                        <!-- Left Sidebar: Frosted Glass Panel (Visible on Desktop lg+) -->
                        <aside class="hidden lg:flex monolith-glass-panel p-6 flex-col justify-between min-h-[640px] lg:min-h-[760px] select-none">
                            
                            <!-- Top: Brand Header -->
                            <div class="space-y-8">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 min-w-[44px] min-h-[44px] max-w-[44px] max-h-[44px] rounded-2xl bg-white flex items-center justify-center p-1.5 shadow-md border border-white/60 overflow-hidden shrink-0" style="width: 44px; height: 44px;">
                                        <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMAIA 7" class="w-full h-full object-contain" style="width: 100%; height: 100%;">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-extrabold text-base tracking-tight text-zinc-900 dark:text-white uppercase leading-none">Al Azhar <span class="text-amber-500">7</span></span>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold tracking-wider uppercase mt-1">Integrated Management System</span>
                                    </div>
                                </div>

                                <!-- Navigation Menu Pills -->
                                <nav class="space-y-2">
                                    <a href="#fitur" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white/90 dark:bg-zinc-800/90 text-zinc-900 dark:text-white font-bold text-xs shadow-sm border border-white/80 dark:border-white/10 transition-all duration-200 hover:scale-[1.02]">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                        </svg>
                                        <span>Fitur Utama</span>
                                    </a>

                                    <a href="#keunggulan" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/5 font-semibold text-xs transition-all duration-150">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                        <span>Keunggulan</span>
                                    </a>

                                    <a href="#sebaran" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/5 font-semibold text-xs transition-all duration-150">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span>Konektivitas</span>
                                    </a>

                                    <a href="#simulator" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/5 font-semibold text-xs transition-all duration-150">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Demo Interaktif</span>
                                    </a>

                                    <a href="#faq" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/5 font-semibold text-xs transition-all duration-150">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span>Tanya Jawab</span>
                                    </a>
                                </nav>
                            </div>

                            <!-- Bottom: School Avatar & Login Action Box -->
                            <div class="pt-6 border-t border-white/60 dark:border-white/10 space-y-3">
                                <div class="flex items-center gap-3 bg-white/60 dark:bg-zinc-800/60 p-2.5 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">
                                        7
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-zinc-900 dark:text-white">SMA Islam Al Azhar 7</span>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">Solo Baru • Sukoharjo</span>
                                    </div>
                                </div>

                                @auth
                                    <a href="{{ url('/dashboard') }}" 
                                       class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-xs uppercase tracking-wider shadow-lg hover:scale-[1.02] active:scale-98 transition duration-200">
                                        <span>Buka Dashboard</span>
                                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-xs uppercase tracking-wider shadow-lg hover:scale-[1.02] active:scale-98 transition duration-200">
                                        <span>Akses Portal Masuk</span>
                                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                        </svg>
                                    </a>
                                @endauth
                            </div>
                        </aside>

                        <!-- Right Main Content Canvas (Col 9) -->
                        <main class="space-y-4 sm:space-y-6 w-full min-w-0">
                            
                            <!-- Top Bar: Frosted Glass Navigation Pill (Desktop/Tablet) -->
                            <div class="monolith-glass-panel p-2.5 sm:p-3.5 hidden sm:flex flex-row items-center justify-between gap-4 select-none">
                                
                                <!-- Center Navigation Tabs (Fitur Utama, Keunggulan, Konektivitas, Demo Interaktif, Tanya Jawab) -->
                                <div class="flex items-center gap-1 sm:gap-1.5 p-1 rounded-full bg-white/70 dark:bg-zinc-800/70 border border-white/80 dark:border-white/10 text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                    <a href="#fitur" class="px-3.5 sm:px-4 py-1.5 rounded-full bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm transition hover:scale-105">Fitur Utama</a>
                                    <a href="#keunggulan" class="px-3.5 sm:px-4 py-1.5 rounded-full hover:text-zinc-900 dark:hover:text-white transition">Keunggulan</a>
                                    <a href="#sebaran" class="px-3.5 sm:px-4 py-1.5 rounded-full hover:text-zinc-900 dark:hover:text-white transition">Konektivitas</a>
                                    <a href="#simulator" class="px-3.5 sm:px-4 py-1.5 rounded-full hover:text-zinc-900 dark:hover:text-white transition">Demo Interaktif</a>
                                    <a href="#faq" class="px-3.5 sm:px-4 py-1.5 rounded-full hover:text-zinc-900 dark:hover:text-white transition">Tanya Jawab</a>
                                </div>

                                <!-- Right Quick Actions -->
                                <div class="flex items-center gap-2.5">
                                    @if (Route::has('login'))
                                        @auth
                                            <a href="{{ url('/dashboard') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-6 py-2 text-xs uppercase tracking-wider rounded-full shadow-md hover:scale-105 active:scale-95 transition duration-200">
                                                Dashboard
                                            </a>
                                        @else
                                            <a href="{{ route('login') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white font-bold px-6 py-2 text-xs uppercase tracking-wider rounded-full shadow-md hover:scale-105 active:scale-95 transition duration-200">
                                                Masuk
                                            </a>
                                        @endauth
                                    @endif
                                </div>
                            </div>

                            <!-- Row 1: 4 Key Metric Cards (2x2 on Mobile, 4-col on Desktop) -->
                            <div class="monolith-top-cards select-none">
                                
                                <!-- Card 1: Total Target Mutqin -->
                                <div class="monolith-card-sm flex flex-col justify-between transform hover:-translate-y-1 transition duration-200">
                                    <div class="flex items-center justify-between">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100/80 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold text-[9px] sm:text-[10px]">+100%</span>
                                    </div>
                                    <div class="mt-2.5 sm:mt-4">
                                        <span class="text-[9px] sm:text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Target Mutqin</span>
                                        <div class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">30 Juz</div>
                                    </div>
                                </div>

                                <!-- Card 2: Monitoring Terpadu -->
                                <div class="monolith-card-sm flex flex-col justify-between transform hover:-translate-y-1 transition duration-200">
                                    <div class="flex items-center justify-between">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-cyan-100 dark:bg-cyan-950 text-cyan-600 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full bg-cyan-100/80 dark:bg-cyan-950/80 text-cyan-700 dark:text-cyan-300 font-bold text-[9px] sm:text-[10px]">+4.2%</span>
                                    </div>
                                    <div class="mt-2.5 sm:mt-4">
                                        <span class="text-[9px] sm:text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Monitoring</span>
                                        <div class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">100% Live</div>
                                    </div>
                                </div>

                                <!-- Card 3: Indikator Karakter Adab -->
                                <div class="monolith-card-sm flex flex-col justify-between transform hover:-translate-y-1 transition duration-200">
                                    <div class="flex items-center justify-between">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100/80 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 font-bold text-[9px] sm:text-[10px]">98.5%</span>
                                    </div>
                                    <div class="mt-2.5 sm:mt-4">
                                        <span class="text-[9px] sm:text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Indikator Adab</span>
                                        <div class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">12+ Poin</div>
                                    </div>
                                </div>

                                <!-- Card 4: Sleek Dark Obsidian Platinum Card -->
                                <div class="bg-zinc-950 text-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[0_15px_35px_rgba(0,0,0,0.15)] flex flex-col justify-between border border-white/10 relative overflow-hidden group">
                                    <div class="absolute -right-8 -bottom-8 w-28 h-28 bg-amber-500/20 rounded-full blur-2xl group-hover:scale-125 transition duration-500"></div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-zinc-400">SMAIA 7</span>
                                        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                                    </div>
                                    <div class="mt-2.5 sm:mt-4">
                                        <span class="text-[9px] sm:text-[10px] text-zinc-400 uppercase font-semibold">TA. 2026/2027</span>
                                        <div class="text-base sm:text-xl font-black text-amber-400 tracking-tight mt-0.5">Generasi Qur'ani</div>
                                    </div>
                                </div>

                            </div>

                            <!-- Row 2: Bento Growth Chart + Recent Activity (Matches Monolith Chart & Activity) -->
                            <div class="monolith-bento-split select-none">
                                
                                <!-- Left Chart Card -->
                                <div class="monolith-glass-panel p-4 sm:p-7 flex flex-col justify-between min-w-0">
                                    
                                    <!-- Chart Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                        <div>
                                            <h3 class="text-base sm:text-lg font-black text-zinc-900 dark:text-white tracking-tight">
                                                Grafik Pertumbuhan Hafalan & Murajaah
                                            </h3>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                Progres setoran kumulatif seluruh kelas reguler & khusus
                                            </p>
                                        </div>

                                        <!-- Month/Year Pill Switch -->
                                        <div class="inline-flex p-1 rounded-full bg-white/80 dark:bg-zinc-800/80 border border-white/80 dark:border-white/10 text-xs font-bold shadow-sm">
                                            <button class="px-4 py-1 rounded-full bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm transition">Bulan</button>
                                            <button class="px-4 py-1 rounded-full text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 transition">Tahun</button>
                                        </div>
                                    </div>

                                    <!-- 3D Soft Rounded Vertical Bar Chart (Jan - Sep) -->
                                    <div class="overflow-x-auto no-scrollbar pt-4 pb-2">
                                        <div class="min-w-[320px] sm:min-w-0 flex items-end justify-between gap-1.5 sm:gap-4 h-44 sm:h-56 px-1">
                                            
                                            <!-- Jan -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-emerald-200/60 dark:bg-emerald-950 rounded-xl sm:rounded-2xl h-20 sm:h-24 group-hover:bg-emerald-300 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-emerald-800 dark:text-emerald-300 transition">6 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">JAN</span>
                                            </div>

                                            <!-- Feb -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-emerald-300/70 dark:bg-emerald-900 rounded-xl sm:rounded-2xl h-28 sm:h-32 group-hover:bg-emerald-400 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-emerald-800 dark:text-emerald-300 transition">10 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">FEB</span>
                                            </div>

                                            <!-- Mar -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-emerald-400/80 dark:bg-emerald-800 rounded-xl sm:rounded-2xl h-24 sm:h-28 group-hover:bg-emerald-500 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-emerald-800 dark:text-emerald-300 transition">14 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">MAR</span>
                                            </div>

                                            <!-- Apr -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-teal-400/80 dark:bg-teal-800 rounded-xl sm:rounded-2xl h-32 sm:h-40 group-hover:bg-teal-500 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-teal-800 dark:text-teal-300 transition">18 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">APR</span>
                                            </div>

                                            <!-- May -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-teal-500/90 dark:bg-teal-700 rounded-xl sm:rounded-2xl h-30 sm:h-36 group-hover:bg-teal-600 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-teal-800 dark:text-teal-300 transition">21 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">MEI</span>
                                            </div>

                                            <!-- Jun -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-cyan-400/80 dark:bg-cyan-800 rounded-xl sm:rounded-2xl h-36 sm:h-48 group-hover:bg-cyan-500 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-cyan-800 dark:text-cyan-300 transition">24 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">JUN</span>
                                            </div>

                                            <!-- Jul -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-cyan-500/90 dark:bg-cyan-700 rounded-xl sm:rounded-2xl h-34 sm:h-44 group-hover:bg-cyan-600 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-cyan-800 dark:text-cyan-300 transition">26 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">JUL</span>
                                            </div>

                                            <!-- Aug -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-amber-400/80 dark:bg-amber-800 rounded-xl sm:rounded-2xl h-32 sm:h-40 group-hover:bg-amber-500 transition duration-200 flex items-center justify-center relative">
                                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-5 sm:-top-6 text-[9px] sm:text-[10px] font-bold text-amber-800 dark:text-amber-300 transition">28 Juz</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-bold text-zinc-400">AGS</span>
                                            </div>

                                            <!-- Sep (Target Peak) -->
                                            <div class="flex flex-col items-center gap-1.5 sm:gap-2 flex-1 group">
                                                <div class="w-full bg-gradient-to-t from-emerald-500 to-teal-400 rounded-xl sm:rounded-2xl h-40 sm:h-52 shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition duration-200 flex items-center justify-center relative">
                                                    <span class="absolute -top-6 sm:-top-7 px-1.5 sm:px-2 py-0.5 rounded-full bg-zinc-900 text-white font-black text-[8px] sm:text-[9px]">30 JUZ</span>
                                                </div>
                                                <span class="text-[9px] sm:text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400">SEP</span>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Right Activity Card -->
                                <div class="monolith-glass-panel p-6 flex flex-col justify-between min-w-0">
                                    
                                    <div>
                                        <div class="flex items-center justify-between pb-4 border-b border-white/80 dark:border-white/10">
                                            <h4 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider">Aktivitas Terkini</h4>
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                        </div>

                                        <!-- Recent Activity Items (Clay Pills) -->
                                        <div class="space-y-3 mt-4">
                                            
                                            <div class="p-3 bg-white/70 dark:bg-zinc-800/70 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                                        📖
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">QS. Al-Kahfi: 1-10</div>
                                                        <div class="text-[10px] text-emerald-600 font-semibold">Hafalan Baru • Mumtaz</div>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] text-zinc-400 font-medium">2m lalu</span>
                                            </div>

                                            <div class="p-3 bg-white/70 dark:bg-zinc-800/70 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-xl bg-cyan-100 dark:bg-cyan-950 text-cyan-600 flex items-center justify-center font-bold text-xs">
                                                        🔄
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">QS. Maryam: 1-30</div>
                                                        <div class="text-[10px] text-cyan-600 font-semibold">Murajaah • Terulang</div>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] text-zinc-400 font-medium">1j lalu</span>
                                            </div>

                                            <div class="p-3 bg-white/70 dark:bg-zinc-800/70 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center font-bold text-xs">
                                                        ⭐
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">Sholat Dhuha & Adab</div>
                                                        <div class="text-[10px] text-amber-600 font-semibold">+10 Poin Karakter</div>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] text-zinc-400 font-medium">Hari ini</span>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="pt-4 text-center">
                                        <a href="#fitur" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center justify-center gap-1">
                                            <span>Lihat Seluruh Log Data</span>
                                            <span>&rarr;</span>
                                        </a>
                                    </div>

                                </div>

                            </div>

                            <!-- Row 3: Quick Program Badges (Matches bottom pills) -->
                            <div class="monolith-bottom-badges select-none">
                                
                                <div class="bg-white/50 dark:bg-zinc-900/60 backdrop-blur-2xl border border-white/80 dark:border-white/10 rounded-2xl p-3.5 shadow-sm flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-xs font-bold">1</span>
                                    <div>
                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">Tahfizh Reguler</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400">Target 3 Juz/Tahun</div>
                                    </div>
                                </div>

                                <div class="bg-white/50 dark:bg-zinc-900/60 backdrop-blur-2xl border border-white/80 dark:border-white/10 rounded-2xl p-3.5 shadow-sm flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-cyan-100 dark:bg-cyan-950 text-cyan-600 flex items-center justify-center text-xs font-bold">2</span>
                                    <div>
                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">Kelas Khusus</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400">Target 30 Juz Mutqin</div>
                                    </div>
                                </div>

                                <div class="bg-white/50 dark:bg-zinc-900/60 backdrop-blur-2xl border border-white/80 dark:border-white/10 rounded-2xl p-3.5 shadow-sm flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center text-xs font-bold">3</span>
                                    <div>
                                        <div class="text-xs font-bold text-zinc-900 dark:text-white">Metode Ummi</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400">Standar Tartil Al Azhar</div>
                                    </div>
                                </div>

                                <a href="{{ route('login') }}" class="bg-zinc-900 hover:bg-zinc-800 text-white rounded-2xl p-3.5 shadow-md flex items-center justify-between group transition duration-200">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-amber-400 font-bold text-xs">🚀</span>
                                        <span class="text-xs font-bold">Akses Portal</span>
                                    </div>
                                    <span class="text-sm font-bold text-amber-400 group-hover:translate-x-1 transition duration-200">&rarr;</span>
                                </a>

                            </div>

                        </main>

                    </div>

                </div>
            </section>

            <!-- SECTION 2: STATS & MUSHAF INTERACTIVE SHOWCASE (Smooth Light Background Transition) -->
            <section id="fitur" class="bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl text-zinc-800 dark:text-zinc-200 border-t border-white/60 dark:border-white/10 py-24 sm:py-28 relative overflow-hidden">
                <!-- Soft Light Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[400px] h-[400px] -top-40 -right-20 bg-teal-100 rounded-full blur-[100px] opacity-60"></div>
                    <div class="absolute w-[400px] h-[400px] -bottom-40 -left-20 bg-amber-100 rounded-full blur-[100px] opacity-60"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col gap-4 max-w-2xl mx-auto mb-20">
                        <h2 class="text-3xl sm:text-4xl font-bold text-zinc-900 tracking-tight leading-tight">
                            Satu Platform, Semua Kebutuhan Pelacakan Hafalan Murid
                        </h2>
                        <p class="text-zinc-500 text-sm sm:text-base leading-relaxed">
                            Dilengkapi integrasi digital mutakhir untuk mencatat capaian tahfidz secara komprehensif, cepat, dan transparan.
                        </p>
                    </div>

                    <!-- Split Columns: Mockup Mushaf & Stats Card -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        
                        <!-- Left Mockup: Mushaf Tracker & Targets -->
                        <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-zinc-200 shadow-[0_20px_50px_rgba(0,0,0,0.04)] animate-float">
                            <!-- Mockup Title Bar -->
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-100 mb-5">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                                    <span class="w-3 h-3 rounded-full bg-green-400"></span>
                                </div>
                                <span class="text-xs font-semibold text-zinc-400">IMS Mushaf Tracker</span>
                                <div class="w-10"></div>
                            </div>

                            <!-- Mockup Body (Surah & Setoran Log UI) -->
                            <div class="space-y-4">
                                <div class="bg-zinc-50 rounded-2xl p-4 border border-zinc-150 flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-zinc-400 font-semibold uppercase tracking-wider">Hafalan Baru</div>
                                        <div class="text-lg font-extrabold text-zinc-900 mt-1">QS. Al-Kahfi: 1-10</div>
                                        <div class="text-[10px] text-teal-600 font-bold mt-1">Status: Lancar (Mumtaz)</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-zinc-400 font-bold">Ustadz Penguji</div>
                                        <div class="text-sm font-semibold text-zinc-800 mt-1">Ahmad Rabbani</div>
                                    </div>
                                </div>

                                <div class="bg-zinc-50 rounded-2xl p-4 border border-zinc-150 flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-zinc-400 font-semibold uppercase tracking-wider">Murajaah Hari Ini</div>
                                        <div class="text-lg font-extrabold text-zinc-900 mt-1">QS. Maryam: 1-30</div>
                                        <div class="text-[10px] text-amber-600 font-bold mt-1">Status: Terulang (Maqbul)</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-zinc-400 font-bold">Target Harian</div>
                                        <div class="text-sm font-semibold text-zinc-800 mt-1">1 Halaman</div>
                                    </div>
                                </div>

                                <!-- Mini Mushaf Text Simulation -->
                                <div class="p-4 bg-teal-50/50 rounded-2xl border border-teal-100 flex flex-col gap-2.5">
                                    <div class="flex items-center justify-between text-xs text-teal-800 font-bold">
                                        <span>Tinjauan Ayat</span>
                                        <span class="font-serif font-normal">سُورَةُ الكَهْفِ</span>
                                    </div>
                                    <p class="text-right font-serif text-lg text-zinc-800 leading-loose py-2">
                                        ٱلْحَمْدُ لِلَّهِ ٱلَّذِىٓ أَنزَلَ عَلَىٰ عَبْدِهِ ٱلْكِتَٰبَ وَلَمْ يَجْعَل لَّهُۥ عِوَجَا ۜ ﴿١﴾
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Card: Stats & Global Achievements -->
                        <div class="lg:col-span-5 flex flex-col gap-6">
                            <h3 class="text-2xl font-bold text-zinc-900 leading-snug">
                                Pantau Progres Capaian Murid dengan Data Akurat
                            </h3>
                            <p class="text-zinc-500 text-sm leading-relaxed">
                                Dilengkapi indikator pencapaian target harian, mingguan, hingga bulanan. Membantu ustadz dan wali murid mengetahui tingkat kelancaran tanpa hambatan koordinasi.
                            </p>

                            <!-- Metric Stack -->
                            <div class="grid grid-cols-2 gap-4 pt-4">
                                <div class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-sm flex flex-col">
                                    <span class="text-xs text-zinc-400 font-bold uppercase">Hafalan Rata-Rata</span>
                                    <span class="text-3xl font-extrabold text-teal-600 mt-2">28 Juz</span>
                                    <span class="text-[10px] text-teal-600 font-semibold mt-1">↑ +2 Halaman/Minggu</span>
                                </div>

                                <div class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-sm flex flex-col">
                                    <span class="text-xs text-zinc-400 font-bold uppercase">Poin Kehadiran</span>
                                    <span class="text-3xl font-extrabold text-amber-600 mt-2">98.5%</span>
                                    <span class="text-[10px] text-zinc-400 font-semibold mt-1">Tingkat Disiplin Tinggi</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 3: KEY ADVANTAGES (Dark background, Spotlight Glow Cards) -->
            <section id="keunggulan" class="bg-[#09090b] bg-grid-pattern text-zinc-100 py-28 relative overflow-hidden border-b border-white/5">
                <!-- Glow Blobs -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] top-[20%] left-[-200px] bg-teal-900 rounded-full blur-[120px] opacity-25"></div>
                    <div class="absolute w-[500px] h-[500px] bottom-[-200px] right-[-200px] bg-amber-900 rounded-full blur-[120px] opacity-20"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col gap-4 max-w-2xl mx-auto mb-20">
                        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight leading-tight">
                            Mengapa Memilih IMS Tracker?
                        </h2>
                        <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Fitur-fitur tangguh yang dirancang spesifik untuk menyederhanakan manajemen tahfidz di sekolah, pondok pesantren, dan rumah tahfidz.
                        </p>
                    </div>

                    <!-- Spotlight Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- Card 1: Input Setoran Cepat (Teal Glow) -->
                        <div class="spotlight-border-card p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Input Setoran Kilat</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Form input teroptimasi memudahkan ustadz merekam hasil setoran hafalan baru maupun murajaah murid dalam hitungan detik.
                            </p>
                        </div>

                        <!-- Card 2: Portal Orang Tua (Amber Glow) -->
                        <div class="spotlight-border-card-amber p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Akses Transparan Wali</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Orang tua dapat masuk langsung untuk melihat log setoran, perkembangan hafalan, adab, dan catatan ustadz pembimbing dari rumah.
                            </p>
                        </div>

                        <!-- Card 3: Rapor PDF & CSV (Teal Glow) -->
                        <div class="spotlight-border-card p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Ekspor Rapor Digital</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Cetak hasil rapor terpadu (Hafalan, Adab, Disiplin) dengan satu klik. Dapat diekspor langsung ke berkas CSV atau PDF berstandar rapi.
                            </p>
                        </div>

                        <!-- Card 4: Poin Disiplin / Tanse (Amber Glow) -->
                        <div class="spotlight-border-card-amber p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Poin Kedisiplinan & Prestasi</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Catat poin pelanggaran disiplin (Tanse) serta apresiasi poin prestasi secara real-time demi membentuk karakter murid yang tangguh.
                            </p>
                        </div>

                        <!-- Card 5: Mushaf & Tafsir (Teal Glow) -->
                        <div class="spotlight-border-card p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Mushaf Qur'an Terpadu</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Membaca Mushaf dan melihat tafsir secara langsung dalam sistem dengan pilihan tema kustom untuk kenyamanan mata.
                            </p>
                        </div>

                        <!-- Card 6: Keamanan Data & Cadangan (Amber Glow) -->
                        <div class="spotlight-border-card-amber p-8 flex flex-col gap-5 border border-white/5 transition-all duration-300 hover:scale-[1.01]"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Cadangan & Keamanan Tinggi</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Database terlindungi dengan enkripsi terbaik, lengkap dengan fitur ekspor dan unduhan cadangan berkala guna menjamin keamanan data.
                            </p>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 4: CONNECTIONS & MAP GEOLOCATION (Globe representation - Dark) -->
            <section id="sebaran" class="bg-[#09090b] text-zinc-100 py-24 relative overflow-hidden border-b border-white/5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col items-center">
                    
                    <div class="text-center flex flex-col gap-4 max-w-2xl mx-auto mb-16">
                        <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white leading-tight">
                            Jaringan Konektivitas Terpadu Seluruh Indonesia
                        </h2>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Menghubungkan ratusan pondok pesantren, sekolah dasar, sekolah menengah, serta rumah tahfidz dalam satu dasbor pusat.
                        </p>
                    </div>

                    <!-- Interactive Dotted Globe Placeholder with pulse pins -->
                    <div class="relative w-full max-w-[650px] aspect-[2/1] bg-zinc-950/40 rounded-3xl border border-white/5 overflow-hidden flex items-center justify-center">
                        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                        
                        <!-- Dotted Map SVG Representation -->
                        <svg class="w-4/5 h-4/5 text-zinc-800 animate-pulse-slow" fill="currentColor" viewBox="0 0 100 50">
                            <!-- Simulated Dots representing Indonesia and nodes -->
                            <circle cx="20" cy="20" r="0.6" class="text-zinc-700"/>
                            <circle cx="25" cy="25" r="0.6" class="text-zinc-700"/>
                            <circle cx="30" cy="23" r="0.8" class="text-zinc-600"/>
                            <circle cx="35" cy="24" r="0.6" class="text-zinc-700"/>
                            <circle cx="45" cy="28" r="0.7" class="text-zinc-600"/>
                            <circle cx="50" cy="30" r="0.8" class="text-teal-500 animate-ping"/> <!-- Pulse Pin Jkt -->
                            <circle cx="50" cy="30" r="1.2" class="text-teal-400"/>
                            <circle cx="58" cy="31" r="0.8" class="text-amber-500 animate-ping"/> <!-- Pulse Pin Sby -->
                            <circle cx="58" cy="31" r="1.2" class="text-amber-400"/>
                            <circle cx="65" cy="32" r="0.8" class="text-zinc-600"/>
                            <circle cx="70" cy="25" r="0.6" class="text-zinc-700"/>
                            <circle cx="75" cy="22" r="0.8" class="text-teal-400"/>
                            <circle cx="85" cy="26" r="0.6" class="text-zinc-700"/>
                        </svg>

                        <!-- Float overlay details representing active metrics -->
                        <div class="absolute bottom-5 left-5 bg-black/60 backdrop-blur-md border border-white/10 px-4 py-2.5 rounded-xl flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 animate-pulse"></span>
                            <span class="text-xs text-zinc-300 font-bold uppercase tracking-wider">12,400+ Murid Aktif</span>
                        </div>

                        <div class="absolute top-5 right-5 bg-black/60 backdrop-blur-md border border-white/10 px-4 py-2.5 rounded-xl flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span class="text-xs text-zinc-300 font-bold uppercase tracking-wider">150+ Lembaga Terhubung</span>
                        </div>
                    </div>

                </div>
            </section>

            <!-- SECTION 5: INTERACTIVE DASHBOARD SIMULATOR PLAYGROUND (Light Background) -->
            <section id="simulator" class="bg-zinc-50 text-zinc-800 bg-grid-pattern-light border-y border-zinc-200/60 py-28 relative overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    
                    <!-- Head -->
                    <div class="text-center flex flex-col gap-4 max-w-2xl mx-auto mb-14">
                        <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900 leading-tight">
                            Simulasi Dasbor Interaktif Kami
                        </h2>
                        <p class="text-zinc-500 text-sm leading-relaxed">
                            Coba dasbor interaktif sekarang. Pilih peran Anda untuk melihat visualisasi alur setoran dan kemudahan antarmuka aplikasi.
                        </p>
                    </div>

                    <!-- Role Tab buttons switcher -->
                    <div class="flex items-center justify-center gap-2 max-w-md mx-auto mb-10 p-1.5 bg-zinc-200/80 rounded-2xl border border-zinc-300/40">
                        <button @click="activeDashboardTab = 'siswa'" 
                                :class="activeDashboardTab === 'siswa' ? 'bg-white text-zinc-900 shadow-md font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                                class="flex-1 py-2 text-xs font-semibold rounded-xl transition-all duration-200">
                            Dasbor Siswa
                        </button>
                        <button @click="activeDashboardTab = 'guru'" 
                                :class="activeDashboardTab === 'guru' ? 'bg-white text-zinc-900 shadow-md font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                                class="flex-1 py-2 text-xs font-semibold rounded-xl transition-all duration-200">
                            Dasbor Ustadz
                        </button>
                        <button @click="activeDashboardTab = 'wali'" 
                                :class="activeDashboardTab === 'wali' ? 'bg-white text-zinc-900 shadow-md font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                                class="flex-1 py-2 text-xs font-semibold rounded-xl transition-all duration-200">
                            Dasbor Wali
                        </button>
                    </div>

                    <!-- Interactive Mockup Screen -->
                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-zinc-200 shadow-[0_30px_70px_rgba(0,0,0,0.06)] min-h-[380px] flex flex-col justify-between transition-all duration-300">
                        
                        <!-- Inner Container transitions based on active role -->
                        <!-- 1. SISWA VIEW -->
                        <div x-show="activeDashboardTab === 'siswa'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6">
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-150">
                                <div>
                                    <h4 class="text-base font-extrabold text-zinc-900">Dasbor Murid — Syamil Rabbani</h4>
                                    <p class="text-[10px] text-zinc-400 font-bold mt-1">Kelas: VIII-A | Rumah Tahfidz Al-Ikhlas</p>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl bg-teal-50 border border-teal-200 text-xs font-bold text-teal-700">Target Tercapai 90%</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-zinc-50 rounded-2xl border border-zinc-200 shadow-inner">
                                    <div class="text-[10px] text-zinc-400 font-bold uppercase">Hafalan Baru</div>
                                    <div class="text-xl font-extrabold text-zinc-900 mt-1">29 Juz</div>
                                    <div class="text-[9px] text-teal-600 font-bold mt-1">Sisa Target: 1 Juz</div>
                                </div>

                                <div class="p-4 bg-zinc-50 rounded-2xl border border-zinc-200 shadow-inner">
                                    <div class="text-[10px] text-zinc-400 font-bold uppercase">Lancarnya Murajaah</div>
                                    <div class="text-xl font-extrabold text-zinc-900 mt-1">15 Juz</div>
                                    <div class="text-[9px] text-amber-600 font-bold mt-1">Poin Nilai: Mumtaz</div>
                                </div>

                                <div class="p-4 bg-zinc-50 rounded-2xl border border-zinc-200 shadow-inner">
                                    <div class="text-[10px] text-zinc-400 font-bold uppercase">Skor Disiplin</div>
                                    <div class="text-xl font-extrabold text-zinc-900 mt-1">100 Poin</div>
                                    <div class="text-[9px] text-teal-600 font-bold mt-1">Status: Tanpa Pelanggaran</div>
                                </div>
                            </div>

                            <!-- Mini Chart Simulator -->
                            <div class="p-4 border border-zinc-150 rounded-2xl flex flex-col gap-3">
                                <div class="flex items-center justify-between text-xs font-bold text-zinc-700">
                                    <span>Statistik Progres Setoran Bulanan</span>
                                    <span>Bulan Juni</span>
                                </div>
                                <div class="h-20 flex items-end gap-2 pt-2 px-1">
                                    <div class="bg-teal-500/20 border border-teal-500/30 rounded-t w-full h-[30%]"></div>
                                    <div class="bg-teal-500/20 border border-teal-500/30 rounded-t w-full h-[50%]"></div>
                                    <div class="bg-teal-500/20 border border-teal-500/30 rounded-t w-full h-[40%]"></div>
                                    <div class="bg-teal-500/20 border border-teal-500/30 rounded-t w-full h-[75%]"></div>
                                    <div class="bg-teal-600 border border-teal-400 rounded-t w-full h-[95%] shadow-[0_0_12px_rgba(20,184,166,0.25)]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. GURU VIEW -->
                        <div x-show="activeDashboardTab === 'guru'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6" style="display: none;">
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-150">
                                <div>
                                    <h4 class="text-base font-extrabold text-zinc-900">Dasbor Ustadz Penguji — Ustadz Ahmad</h4>
                                    <p class="text-[10px] text-zinc-400 font-bold mt-1">Kelas: Halaqah Tahfidz VIII-A</p>
                                </div>
                                <button class="px-4 py-2 bg-teal-600 hover:bg-teal-500 text-xs font-bold text-white rounded-xl transition-colors duration-150 shadow-md shadow-teal-600/10">
                                    + Input Setoran
                                </button>
                            </div>

                            <!-- List of Students under evaluation -->
                            <div class="space-y-3">
                                <div class="p-3 bg-zinc-50 border border-zinc-150 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center font-bold text-teal-700 text-xs">SR</div>
                                        <div>
                                            <div class="text-xs font-bold text-zinc-800">Syamil Rabbani</div>
                                            <div class="text-[9px] text-zinc-400 mt-0.5">Baru saja menyetor QS. Al-Kahfi: 1-10</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] bg-teal-50 border border-teal-200 text-teal-700 font-bold px-2 py-1 rounded">Nilai: Mumtaz</span>
                                </div>

                                <div class="p-3 bg-zinc-50 border border-zinc-150 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center font-bold text-amber-700 text-xs">AM</div>
                                        <div>
                                            <div class="text-xs font-bold text-zinc-800">Aisyah Muthmainnah</div>
                                            <div class="text-[9px] text-zinc-400 mt-0.5">Mengulang setoran QS. Al-Baqarah: 1-20</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] bg-amber-50 border border-amber-200 text-amber-700 font-bold px-2 py-1 rounded">Nilai: Jayyid</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. WALI VIEW -->
                        <div x-show="activeDashboardTab === 'wali'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6" style="display: none;">
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-150">
                                <div>
                                    <h4 class="text-base font-extrabold text-zinc-900">Portal Wali Murid — Bpk. Abdurrahman</h4>
                                    <p class="text-[10px] text-zinc-400 font-bold mt-1">Mengawasi Murid: Syamil Rabbani</p>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse" title="Terhubung"></span>
                            </div>

                            <!-- Parents overview feed -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-teal-50 border border-teal-150 rounded-2xl flex flex-col gap-1.5">
                                    <span class="text-[10px] text-teal-800 font-bold uppercase">Pembaruan Hafalan Terakhir</span>
                                    <div class="text-sm font-extrabold text-zinc-900">Syamil menyetor QS. Al-Kahfi: 1-10</div>
                                    <span class="text-[9px] text-teal-700 font-semibold mt-1">Ustadz Ahmad: "Bacaan lancar, pertahankan tajwid"</span>
                                </div>

                                <div class="p-4 bg-amber-50 border border-amber-150 rounded-2xl flex flex-col gap-1.5">
                                    <span class="text-[10px] text-amber-800 font-bold uppercase">Laporan Adab & Karakter</span>
                                    <div class="text-sm font-extrabold text-zinc-900">Sikap: Sangat Menghormati Guru</div>
                                    <span class="text-[9px] text-amber-700 font-semibold mt-1">Terakhir diperbarui: Juni 2026</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 6: FAQ ACCORDION (Dark Background) -->
            <section id="faq" class="bg-[#09090b] text-zinc-100 py-28 relative overflow-hidden border-b border-white/5">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    
                    <div class="text-center flex flex-col gap-4 mx-auto mb-16 max-w-2xl">
                        <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white leading-tight">
                            Pertanyaan yang Sering Diajukan
                        </h2>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Butuh bantuan lebih lanjut? Berikut jawaban untuk beberapa pertanyaan umum mengenai penggunaan sistem kami.
                        </p>
                    </div>

                    <!-- FAQ List with Alpine state toggles -->
                    <div class="space-y-4">
                        
                        <!-- Item 1 -->
                        <div x-data="{ open: false }" class="p-5 rounded-2xl bg-zinc-900/60 border border-white/5 hover:border-white/10 transition-all duration-150">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-white">
                                <span>Bagaimana ustadz menginput data setoran?</span>
                                <svg :class="open ? 'rotate-180 text-teal-400' : 'text-zinc-500'" class="w-5 h-5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="mt-4 text-sm text-zinc-400 leading-relaxed">
                                Ustadz dapat menginput data langsung melalui form "Quick Input" di dasbor mereka, cukup memilih nama siswa, surah, ayat awal/akhir, dan nilai kelancaran. Semua proses memakan waktu kurang dari 10 detik.
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div x-data="{ open: false }" class="p-5 rounded-2xl bg-zinc-900/60 border border-white/5 hover:border-white/10 transition-all duration-150">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-white">
                                <span>Apakah data kemajuan murid aman dari kehilangan?</span>
                                <svg :class="open ? 'rotate-180 text-teal-400' : 'text-zinc-500'" class="w-5 h-5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="mt-4 text-sm text-zinc-400 leading-relaxed">
                                Ya. Kami menggunakan basis data terenkripsi dan sistem cadangan (database backups) otomatis yang terhubung ke penyimpanan awan. Anda juga dapat mengunduh berkas cadangan secara manual kapan saja dari dasbor Super Admin.
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div x-data="{ open: false }" class="p-5 rounded-2xl bg-zinc-900/60 border border-white/5 hover:border-white/10 transition-all duration-150">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-white">
                                <span>Bagaimana wali murid memantau perkembangan putra-putrinya?</span>
                                <svg :class="open ? 'rotate-180 text-teal-400' : 'text-zinc-500'" class="w-5 h-5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="mt-4 text-sm text-zinc-400 leading-relaxed">
                                Setiap wali murid akan diberikan akun akses khusus. Begitu masuk, mereka akan diarahkan langsung ke dasbor berisi riwayat setoran, persentase target hafalan yang tercapai, serta skor perilaku (adab) harian.
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 7: SUNSET HORIZON CTA (Dark Background, Huge Ambient Lighting Sunset Glow) -->
            <section class="relative bg-black text-zinc-100 py-36 overflow-hidden flex flex-col items-center justify-center text-center">
                <!-- Massive Bottom Horizon Glow (Sunset) -->
                <div class="glow-horizon"></div>
                <div class="glow-horizon-core"></div>

                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col items-center gap-6">
                    <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                        Siap Mewujudkan Generasi Rabbani Rapi & Terstruktur?
                    </h2>
                    <p class="text-zinc-400 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
                        Mulai langkah digitalisasi tahfidz Anda sekarang bersama ratusan lembaga lainnya di Indonesia. Cepat, aman, dan mudah digunakan.
                    </p>

                    <!-- CTA Action stack -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-6">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-8 py-4 text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600 border border-teal-500/20 shadow-2xl shadow-teal-500/20 transition-all duration-200 hover:-translate-y-0.5">
                                Masuk Dasbor Aplikasi
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-8 py-4 text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600 border border-teal-500/20 shadow-2xl shadow-teal-500/20 transition-all duration-200 hover:-translate-y-0.5">
                                Masuk Aplikasi
                            </a>
                        @endif
                        <a href="https://wa.me/628989789085" target="_blank" class="bg-white/5 border border-white/10 text-zinc-300 hover:bg-white/10 hover:text-white px-8 py-4 text-sm font-semibold rounded-xl transition-all duration-150 hover:-translate-y-0.5 flex items-center gap-2">
                            Hubungi Pengembang
                        </a>
                    </div>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="w-full bg-black border-t border-white/5 py-10 text-center relative z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                    <p class="text-xs text-zinc-500">
                        &copy; 2026 IMS (Integrated Management System). Made by Native Omni.
                    </p>
                    <div class="flex items-center gap-6 text-xs text-zinc-500">
                        <a href="#" class="hover:text-zinc-300 transition-colors">Syarat Ketentuan</a>
                        <a href="#" class="hover:text-zinc-300 transition-colors">Kebijakan Privasi</a>
                    </div>
                </div>
            </footer>

        </div>
    </body>
</html>
