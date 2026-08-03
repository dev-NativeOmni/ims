<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

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
                <main class="flex-1 py-4 px-3 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
