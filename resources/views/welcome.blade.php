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
                    <!-- Brand Logo: Pure School Logo & Pure Gemilang Banner Logo -->
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-10 w-10 sm:h-11 sm:w-11 object-contain shrink-0">
                        <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-8 sm:h-9 max-w-[170px] sm:max-w-[220px] object-contain">
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
            <!-- SECTION 1: CLEAN MINIMALIST HERO (SUNSET SCHOOL & GLASSMORPHIC VIGNETTE)  -->
            <!-- ========================================================================= -->
            <section class="w-full relative min-h-screen flex flex-col justify-between py-8 px-4 sm:px-6 lg:px-8 overflow-hidden bg-cover bg-center bg-no-repeat"
                     style="background-image: url('{{ asset('images/school_sunset_bg.jpg') }}');">
                
                <!-- Layer 1: Glassmorphic Vignette & Duotone Gemilang Ambient Glow -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <!-- Radial Dark Vignette (Smooth Center Exposure & Dark Edge Framing) -->
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(9,9,11,0.22)_0%,rgba(9,9,11,0.58)_45%,rgba(9,9,11,0.95)_100%)]"></div>
                    
                    <!-- Duotone Gemilang Ambient Glows (Sunset Tangerine Orange & Royal Cobalt Blue) -->
                    <div class="absolute -top-32 left-1/4 w-[500px] h-[500px] bg-orange-500/18 rounded-full blur-[140px]"></div>
                    <div class="absolute top-1/3 -right-32 w-[550px] h-[550px] bg-blue-600/22 rounded-full blur-[150px]"></div>
                    <div class="absolute -bottom-24 left-1/3 w-[600px] h-[350px] bg-gradient-to-t from-[#09090b] via-[#09090b]/80 to-transparent"></div>
                </div>

                <!-- Top Navbar: Frosted Glass Pill Header -->
                <header class="max-w-6xl mx-auto w-full relative z-20">
                    <div class="p-2 sm:p-2.5 rounded-full bg-black/40 dark:bg-zinc-900/50 backdrop-blur-xl border border-white/15 shadow-xl flex items-center justify-between gap-2 sm:gap-3">
                        
                        <!-- Left Brand Logo: Pure School Logo & Pure Gemilang Banner Logo (Original Size) -->
                        <div class="flex items-center gap-2.5 sm:gap-3 pl-1 sm:pl-2">
                            <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-9 w-9 sm:h-11 sm:w-11 object-contain shrink-0 drop-shadow-md">
                            <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-7 sm:h-9 max-w-[160px] sm:max-w-[220px] object-contain drop-shadow-md brightness-110 shrink-0">
                        </div>

                        <!-- Center Navigation Links (Hidden on Mobile) -->
                        <nav class="hidden md:flex items-center gap-1 text-xs font-semibold text-zinc-200">
                            <a href="#fitur" class="px-3.5 py-1.5 rounded-full hover:text-white hover:bg-white/10 transition-all duration-150">Fitur Utama</a>
                            <a href="#keunggulan" class="px-3.5 py-1.5 rounded-full hover:text-white hover:bg-white/10 transition-all duration-150">Keunggulan</a>
                            <a href="#sebaran" class="px-3.5 py-1.5 rounded-full hover:text-white hover:bg-white/10 transition-all duration-150">Konektivitas</a>
                            <a href="#simulator" class="px-3.5 py-1.5 rounded-full hover:text-white hover:bg-white/10 transition-all duration-150">Demo Interaktif</a>
                            <a href="#faq" class="px-3.5 py-1.5 rounded-full hover:text-white hover:bg-white/10 transition-all duration-150">Tanya Jawab</a>
                        </nav>

                        <!-- Right Auth Action Button (Compact text on mobile) -->
                        <div class="pr-1 sm:pr-1.5 shrink-0">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" 
                                       class="inline-flex items-center justify-center px-3 py-1.5 sm:px-6 sm:py-2 rounded-full text-white font-bold text-[10px] sm:text-xs uppercase tracking-wider bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 transition-all duration-200">
                                        <span>Dashboard</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="inline-flex items-center justify-center px-3 py-1.5 sm:px-6 sm:py-2 rounded-full text-white font-bold text-[10px] sm:text-xs uppercase tracking-wider bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 transition-all duration-200">
                                        <span>Masuk</span>
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </header>

                <!-- Center Hero Stage: Sleek & Balanced Typography -->
                <div class="max-w-4xl mx-auto w-full relative z-10 text-center flex flex-col items-center justify-center my-auto py-8 sm:py-14">
                    
                    <!-- Pill Badge: Generasi Mulia Islami Cemerlang -->
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3.5 py-1 sm:px-4 sm:py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 shadow-sm text-[10px] sm:text-xs font-bold text-amber-300 uppercase tracking-wider mb-4 sm:mb-6 animate-pulse">
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.2L12 16.8 5.7 21.2 8 14l-6-4.6h7.6z" />
                        </svg>
                        <span>Generasi Mulia Islami Cemerlang</span>
                    </div>

                    <!-- Main Hero Title -->
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-snug sm:leading-tight drop-shadow-[0_4px_16px_rgba(0,0,0,0.6)] px-2">
                        Integrated Management System
                    </h1>

                    <!-- Hero Subtitle in Indonesian -->
                    <p class="mt-3 sm:mt-5 text-xs sm:text-base lg:text-lg text-zinc-200 max-w-2xl mx-auto leading-relaxed font-normal drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] px-2">
                        Platform Digital Terpadu <strong class="text-white font-semibold">SMA Islam Al Azhar 7 Sukoharjo</strong> untuk pemantauan tahfizh mutqin, pembiasaan karakter adab, dan kemajuan akademik santri secara real-time.
                    </p>

                    <!-- Dual Action CTA Buttons (Proportionate on Mobile) -->
                    <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row items-center justify-center gap-2.5 sm:gap-3.5 w-full sm:w-auto max-w-xs sm:max-w-none mx-auto">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-white font-bold text-xs sm:text-sm tracking-wider uppercase bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 shadow-[0_0_30px_rgba(249,115,22,0.4)] hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                                <span>Buka Dashboard</span>
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-white font-bold text-xs sm:text-sm tracking-wider uppercase bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 shadow-[0_0_30px_rgba(249,115,22,0.4)] hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                                <span>Akses Portal Masuk</span>
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endauth

                        <a href="#fitur" 
                           class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-zinc-200 hover:text-white font-bold text-xs sm:text-sm tracking-wide bg-black/40 hover:bg-white/10 backdrop-blur-md border border-white/20 shadow-md hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                            <span>Jelajahi Fitur</span>
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Bottom Floating Metric Dock: Compact 3-Column Horizontal Dock (No vertical stacking on mobile) -->
                <div class="max-w-4xl mx-auto w-full relative z-10 pb-2">
                    <div class="grid grid-cols-3 gap-1.5 sm:gap-4 p-2 sm:p-4 rounded-2xl sm:rounded-3xl bg-black/45 backdrop-blur-2xl border border-white/15 shadow-[0_15px_40px_rgba(0,0,0,0.5)]">
                        
                        <!-- Metric 1: Tahfizh 30 Juz -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3.5 p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition duration-200">
                            <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <div class="text-xs sm:text-lg font-black text-white leading-tight">30 Juz</div>
                                <div class="text-[9px] sm:text-[11px] text-zinc-300 font-medium leading-tight mt-0.5">Tahfizh Mutqin</div>
                            </div>
                        </div>

                        <!-- Metric 2: 100% Real-Time Monitoring -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3.5 p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition duration-200">
                            <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <div class="text-xs sm:text-lg font-black text-white leading-tight">100% Live</div>
                                <div class="text-[9px] sm:text-[11px] text-zinc-300 font-medium leading-tight mt-0.5">Monitoring</div>
                            </div>
                        </div>

                        <!-- Metric 3: 12+ Poin Pembiasaan Adab -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3.5 p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition duration-200">
                            <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <div class="text-xs sm:text-lg font-black text-white leading-tight">12+ Poin</div>
                                <div class="text-[9px] sm:text-[11px] text-zinc-300 font-medium leading-tight mt-0.5">Adab Santri</div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 2: STATS & MUSHAF INTERACTIVE SHOWCASE (Sunset Glassmorphic Showcase) -->
            <section id="fitur" class="bg-[#09090b] text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-t border-b border-white/10">
                <!-- Ambient Sunset & Cobalt Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] -top-32 -left-32 bg-orange-500/15 rounded-full blur-[140px]"></div>
                    <div class="absolute w-[500px] h-[500px] -bottom-32 -right-32 bg-blue-600/15 rounded-full blur-[140px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-2xl mx-auto mb-16 sm:mb-20">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-bold uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 animate-pulse"></span>
                            Fitur Unggulan IMS
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                            Satu Platform, Semua Kebutuhan Pelacakan Tahfidz
                        </h2>
                        <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Pencatatan hafalan harian, murajaah multi-surat cerdas, input spreadsheet secepat kilat, dan laporan WhatsApp otomatis dalam satu sentuhan.
                        </p>
                    </div>

                    <!-- Split Columns: Glassmorphic Mockup Dashboard & Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        
                        <!-- Left Mockup: Glassmorphic Tahfidz Tracker Hub -->
                        <div class="lg:col-span-7 bg-zinc-900/80 backdrop-blur-2xl rounded-3xl p-5 sm:p-7 border border-white/15 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] relative group">
                            <!-- Subtle Mockup Glow Accent -->
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>

                            <!-- Mockup Window Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-5">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                    <span class="text-xs font-bold text-zinc-400 ml-2 hidden sm:inline">IMS Tahfidz Hub • Dashboard Live</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                        Sesi Aktif
                                    </span>
                                </div>
                            </div>

                            <!-- Mockup Body (Hafalan Baru, Murojaah Cerdas, Mushaf Preview) -->
                            <div class="space-y-3.5">
                                
                                <!-- Card 1: Hafalan Baru -->
                                <div class="bg-black/40 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-orange-500/30 transition-all duration-200">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-orange-400 font-bold shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[11px] text-zinc-400 font-bold uppercase tracking-wider">Hafalan Baru (Ziyadah)</div>
                                            <div class="text-base sm:text-lg font-bold text-white">QS. Al-Kahfi : 1 – 10</div>
                                            <div class="text-[11px] text-emerald-400 font-medium flex items-center gap-1 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Mumtaz • 15 Baris
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sm:text-right flex sm:flex-col justify-between items-center sm:items-end border-t sm:border-t-0 border-white/5 pt-2 sm:pt-0">
                                        <div class="text-[10px] text-zinc-400 font-semibold uppercase">Ustadz Penguji</div>
                                        <div class="text-xs sm:text-sm font-semibold text-zinc-200">Ust. Ahmad Rabbani</div>
                                    </div>
                                </div>

                                <!-- Card 2: Murajaah Multi-Surat Cerdas -->
                                <div class="bg-black/40 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-amber-500/30 transition-all duration-200">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[11px] text-zinc-400 font-bold uppercase tracking-wider">Murajaah Multi-Surah</div>
                                            <div class="text-base sm:text-lg font-bold text-white">QS. An-Naba' – 'Abasa</div>
                                            <div class="text-[11px] text-amber-400 font-medium flex items-center gap-1 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Penanda Terakhir: QS. 'Abasa
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sm:text-right flex sm:flex-col justify-between items-center sm:items-end border-t sm:border-t-0 border-white/5 pt-2 sm:pt-0">
                                        <div class="text-[10px] text-zinc-400 font-semibold uppercase">Status Kelancaran</div>
                                        <div class="text-xs sm:text-sm font-semibold text-emerald-400">Mutqin (Lancar)</div>
                                    </div>
                                </div>

                                <!-- Card 3: Interactive Mushaf Typography Preview -->
                                <div class="p-4 bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-transparent rounded-2xl border border-orange-500/20 flex flex-col gap-2">
                                    <div class="flex items-center justify-between text-xs text-orange-300 font-bold">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                                            Tinjauan Mushaf Madinah
                                        </span>
                                        <span class="font-serif text-sm font-normal text-amber-200">سُورَةُ الكَهْفِ</span>
                                    </div>
                                    <p class="text-right font-serif text-base sm:text-xl text-zinc-100 leading-loose pt-1">
                                        ٱلْحَمْدُ لِلَّهِ ٱلَّذِىٓ أَنزَلَ عَلَىٰ عَبْدِهِ ٱلْكِتَٰبَ وَلَمْ يَجْعَل لَّهُۥ عِوَجَا ۜ ﴿١﴾
                                    </p>
                                    <div class="flex items-center justify-between text-[11px] text-zinc-400 border-t border-white/10 pt-2 mt-1">
                                        <span>Target Juz 15</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 sm:w-32 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                                <div class="w-4/5 h-full bg-gradient-to-r from-orange-500 to-amber-400 rounded-full"></div>
                                            </div>
                                            <span class="font-bold text-orange-300">80%</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Right Column: Glassmorphic Feature Highlights & Metrics -->
                        <div class="lg:col-span-5 flex flex-col gap-6">
                            <div class="space-y-3">
                                <h3 class="text-2xl sm:text-3xl font-bold text-white leading-snug">
                                    Pantau Capaian Tahfidz dengan Akurasi Real-Time
                                </h3>
                                <p class="text-zinc-400 text-sm leading-relaxed">
                                    Sistem pelacakan terpadu yang memadukan input cerdas, grafik analitik target kurikulum, evaluasi adab santri, dan transmisi otomatis ke orang tua.
                                </p>
                            </div>

                            <!-- Feature Points with Sunset Icons -->
                            <div class="space-y-3.5">
                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-orange-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Input Spreadsheet & Cepat</h4>
                                        <p class="text-xs text-zinc-400 mt-0.5">Input puluhan santri per halaqah hanya dalam 30 detik tanpa reload halaman.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-emerald-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Laporan Harian WhatsApp Terpadu</h4>
                                        <p class="text-xs text-zinc-400 mt-0.5">Generator pesan otomatis mencakup ziyadah, seluruh surat murajaah, dan adab santri.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-blue-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Portal Wali Murid 360°</h4>
                                        <p class="text-xs text-zinc-400 mt-0.5">Transparansi penuh rekam jejak hafalan, riwayat murajaah, dan evaluasi bulanan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 3: KEY ADVANTAGES (Sunset Glassmorphic Spotlight Cards) -->
            <section id="keunggulan" class="bg-[#09090b] text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-b border-white/10">
                <!-- Ambient Sunset & Cobalt Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] top-[15%] left-[-150px] bg-orange-500/10 rounded-full blur-[140px]"></div>
                    <div class="absolute w-[500px] h-[500px] bottom-[-150px] right-[-150px] bg-blue-600/10 rounded-full blur-[140px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-3xl mx-auto mb-16 sm:mb-20">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            Keunggulan Sistem
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                            Mengapa Memilih IMS ?
                        </h2>
                        <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Fitur-fitur tangguh yang dirancang spesifik untuk menyederhanakan manajemen Tahfizh di sekolah, Pondok Pesantren, dan Rumah Tahfizh.
                        </p>
                    </div>

                    <!-- 5 Spotlight Cards Grid (Balanced Glassmorphic Layout) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Card 1: Input Setoran (Sunset Orange Glow) -->
                        <div class="spotlight-border-card p-7 sm:p-8 flex flex-col gap-4 bg-zinc-900/60 backdrop-blur-xl rounded-3xl border border-white/10 hover:border-orange-500/40 transition-all duration-300 hover:-translate-y-1 group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-orange-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white group-hover:text-orange-300 transition-colors">Input Setoran</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Form input teroptimasi memudahkan ustadz merekam hasil setoran hafalan baru maupun murajaah murid dalam hitungan detik.
                            </p>
                        </div>

                        <!-- Card 2: Akses Transparan Wali (Warm Amber Glow) -->
                        <div class="spotlight-border-card-amber p-7 sm:p-8 flex flex-col gap-4 bg-zinc-900/60 backdrop-blur-xl rounded-3xl border border-white/10 hover:border-amber-500/40 transition-all duration-300 hover:-translate-y-1 group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-300 transition-colors">Akses Transparan Wali</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Orang tua dapat masuk langsung untuk melihat log setoran, perkembangan hafalan, adab, dan catatan ustadz pembimbing dari rumah.
                            </p>
                        </div>

                        <!-- Card 3: Poin Kedisiplinan & Prestasi (Emerald / Tanse Glow) -->
                        <div class="spotlight-border-card p-7 sm:p-8 flex flex-col gap-4 bg-zinc-900/60 backdrop-blur-xl rounded-3xl border border-white/10 hover:border-emerald-500/40 transition-all duration-300 hover:-translate-y-1 group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white group-hover:text-emerald-300 transition-colors">Poin Kedisiplinan & Prestasi</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Catat poin pelanggaran disiplin (Tanse) serta apresiasi poin prestasi secara real-time demi membentuk karakter murid yang tangguh.
                            </p>
                        </div>

                        <!-- Card 4: Mushaf Qur'an Terpadu (Cobalt / Teal Glow) -->
                        <div class="spotlight-border-card p-7 sm:p-8 flex flex-col gap-4 bg-zinc-900/60 backdrop-blur-xl rounded-3xl border border-white/10 hover:border-teal-500/40 transition-all duration-300 hover:-translate-y-1 group md:col-span-1 lg:col-span-1"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-teal-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white group-hover:text-teal-300 transition-colors">Mushaf Qur'an Terpadu</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">
                                Membaca Mushaf dan melihat tafsir secara langsung dalam sistem dengan pilihan tema kustom untuk kenyamanan mata.
                            </p>
                        </div>

                        <!-- Card 5: Cadangan & Keamanan Tinggi (Royal Blue Glow) -->
                        <div class="spotlight-border-card-amber p-7 sm:p-8 flex flex-col gap-4 bg-zinc-900/60 backdrop-blur-xl rounded-3xl border border-white/10 hover:border-blue-500/40 transition-all duration-300 hover:-translate-y-1 group md:col-span-1 lg:col-span-2"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white group-hover:text-blue-300 transition-colors">Cadangan & Keamanan Tinggi</h3>
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
