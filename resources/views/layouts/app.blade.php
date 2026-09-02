<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IMS SMAIA 7') }}</title>

        <!-- PWA & Apple iOS Metadata -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#059669">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="IMS SMAIA 7">
        <link rel="apple-touch-icon" href="/images/logo_alazhar7.png">
        <link rel="icon" type="image/png" href="/images/logo_alazhar7.png">

        <!-- iOS Safari BFCache & PWA Service Worker -->
        <script>
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function(err) {});
                });
            }
        </script>

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
    <body class="font-sans antialiased bg-zinc-50 text-zinc-800 dark:bg-[#09090b] dark:text-zinc-100 transition-colors duration-200 selection:bg-indigo-500 selection:text-white">
        <!-- Glow background blobs -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="glow-blob bg-indigo-600 w-[500px] h-[500px] -top-60 -left-60 opacity-5 dark:opacity-20 transition-opacity duration-300"></div>
            <div class="glow-blob bg-purple-600 w-[450px] h-[450px] top-[30%] -right-40 opacity-5 dark:opacity-20 transition-opacity duration-300"></div>
            <div class="glow-blob bg-emerald-600 w-[400px] h-[400px] -bottom-40 left-[20%] opacity-5 dark:opacity-15 transition-opacity duration-300"></div>
        </div>

        <div class="min-h-screen relative z-10 flex flex-col" x-data="{ sidebarOpen: false, dark: document.documentElement.classList.contains('dark'), toggleTheme() { this.dark = !this.dark; if (this.dark) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); } } }">
            @if (session()->has('impersonated_by'))
                <div class="bg-amber-600 text-white px-4 py-2.5 shadow-md flex items-center justify-between z-50 text-xs sm:text-sm font-medium sticky top-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-200 animate-ping"></span>
                        <span>⚠️ <strong>Mode Impersonasi:</strong> Anda sedang meninjau sistem sebagai <strong>{{ auth()->user()?->name }}</strong> ({{ auth()->user()?->role?->display_name ?? auth()->user()?->role?->name }}).</span>
                    </div>
                    <form method="POST" action="{{ route('impersonate.stop') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-md text-xs font-semibold backdrop-blur transition cursor-pointer">
                            Kembali ke Super Admin &rarr;
                        </button>
                    </form>
                </div>
            @endif

            @include('layouts.navigation')

            <div class="flex-grow flex flex-col min-h-screen">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white/80 dark:bg-[#09090b]/50 backdrop-blur-lg border-b border-zinc-200/50 dark:border-white/5 sticky top-0 z-10 transition-colors duration-200">
                        <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 py-4 px-3 sm:px-6 lg:px-8 pb-24 md:pb-6">
                    {{ $slot }}
                </main>
            </div>

            <!-- Mobile Bottom Quick Navigation -->
            @include('layouts.mobile-bottom-nav')
        </div>

        <!-- Global Network Connection Toast -->
        <div x-data="{ isOnline: navigator.onLine, showToast: false }"
             x-init="
                 window.addEventListener('online', () => { isOnline = true; showToast = true; setTimeout(() => showToast = false, 4000); });
                 window.addEventListener('offline', () => { isOnline = false; showToast = true; });
             "
             x-show="showToast"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-8 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-8 opacity-0"
             class="fixed bottom-4 right-4 z-50 px-4 py-2.5 rounded-lg shadow-xl text-xs sm:text-sm font-semibold flex items-center gap-2 border"
             :class="isOnline ? 'bg-emerald-800 text-emerald-100 border-emerald-600' : 'bg-red-800 text-red-100 border-red-600'"
             style="display: none;">
            <template x-if="isOnline">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Koneksi internet kembali terhubung
                </span>
            </template>
            <template x-if="!isOnline">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-red-300 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Koneksi terputus (Offline). Pengisian data tetap aman.
                </span>
            </template>
        </div>
    </body>
</html>
