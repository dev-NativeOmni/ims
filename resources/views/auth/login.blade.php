@php
    $logo = \App\Models\Setting::get('logo');
    $namaInstansi = \App\Models\Setting::get('nama_instansi');
    $loginBg = \App\Models\Setting::get('login_bg');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IMS SMAIA 7') }} - {{ __('Masuk') }}</title>

    <!-- PWA & Apple iOS Metadata -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f59e0b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="IMS SMAIA 7">
    <link rel="apple-touch-icon" href="/images/logo_alazhar7.png">
    <link rel="icon" type="image/png" href="/images/logo_alazhar7.png">

    <!-- iOS Safari BFCache & Session Expiry Safeguard -->
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

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & JS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="min-h-[100dvh] flex items-center justify-center p-4 sm:p-6 antialiased relative overflow-x-hidden selection:bg-amber-500 selection:text-white bg-cover bg-center bg-no-repeat bg-fixed"
      style="background-image: url('{{ $loginBg ? asset('storage/' . $loginBg) : asset('images/school_sunset_bg.jpg') }}');">

    <!-- Ambient Layer: Contrast Overlays & Duotone Glows -->
    <div class="absolute inset-0 pointer-events-none z-0">
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/50 to-black/80"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(9,9,11,0.7)_100%)]"></div>
        
        <!-- Duotone Ambient Glows -->
        <div class="absolute -top-32 left-1/4 w-[500px] h-[500px] bg-orange-500/20 rounded-full blur-[140px]"></div>
        <div class="absolute top-1/3 -right-32 w-[550px] h-[550px] bg-blue-500/15 rounded-full blur-[150px]"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-[420px] relative z-10 my-auto">

        <!-- Glassmorphism Login Card (Matching Mockup Reference) -->
        <div class="relative w-full p-7 sm:p-9 rounded-[2.25rem] bg-gradient-to-b from-white/15 via-white/[0.08] to-white/[0.03] backdrop-blur-3xl border border-white/30 dark:border-amber-400/25 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.8),inset_0_1px_1px_rgba(255,255,255,0.4)] text-white overflow-hidden transition-all duration-300">
            
            <!-- Top Specular Glare / Rim Reflection Effect -->
            <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-48 h-20 bg-gradient-to-b from-white/40 via-white/10 to-transparent blur-xl pointer-events-none rounded-full"></div>
            <div class="absolute top-0 inset-x-0 h-[1.5px] bg-gradient-to-r from-transparent via-amber-300/80 to-transparent pointer-events-none"></div>

            <!-- Ambient Glow Blobs Inside Card -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-600/15 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Header Brand Emblem & Title -->
            <div class="flex flex-col items-center justify-center text-center mb-6 relative z-10">
                <!-- Glowing Emblem Container -->
                <div class="relative mb-3.5 flex items-center justify-center">
                    <div class="absolute inset-0 bg-amber-400/25 rounded-full blur-xl animate-pulse"></div>
                    <div class="relative w-18 h-18 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-b from-white/20 to-black/40 backdrop-blur-xl border border-amber-400/50 p-2.5 shadow-[0_0_30px_rgba(245,158,11,0.35)] flex items-center justify-center">
                        @if ($logo)
                            <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="w-full h-full object-contain drop-shadow-md" />
                        @else
                            <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMAIA 7" class="w-full h-full object-contain drop-shadow-md">
                        @endif
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-black font-display text-white tracking-tight leading-tight">
                    Portal <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400">Masuk</span>
                </h1>
                
                @if ($namaInstansi)
                    <p class="text-xs text-zinc-300/90 font-semibold mt-1 uppercase tracking-wider">{{ $namaInstansi }}</p>
                @else
                    <p class="text-xs text-zinc-300/80 font-medium mt-1">SMA Islam Al Azhar 7 Sukoharjo</p>
                @endif
            </div>

            <!-- Session Status Alert -->
            @if (session('status'))
                <div class="mb-4 text-center text-xs font-bold text-amber-300 bg-amber-500/15 border border-amber-400/30 p-3 rounded-xl backdrop-blur-md">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4 relative z-10">
                @csrf

                <!-- Username / Email Field with Icon -->
                <div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-amber-300/80">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="username" 
                               value="{{ old('username') }}" 
                               required 
                               autofocus 
                               autocomplete="username"
                               placeholder="Email / Username"
                               class="w-full pl-11 pr-4 py-3.5 rounded-2xl bg-black/35 border border-amber-400/40 focus:border-amber-400 text-white placeholder-zinc-400 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30 backdrop-blur-md transition-all duration-200">
                    </div>
                    @if ($errors->has('username'))
                        <p class="text-xs font-semibold text-rose-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first('username') }}</span>
                        </p>
                    @endif
                </div>

                <!-- Password Field with Lock Icon & Show/Hide Eye Toggle -->
                <div x-data="{ showPass: false }">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-amber-300/80">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" 
                               name="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="Password"
                               class="w-full pl-11 pr-11 py-3.5 rounded-2xl bg-black/35 border border-amber-400/40 focus:border-amber-400 text-white placeholder-zinc-400 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30 backdrop-blur-md transition-all duration-200">
                        
                        <!-- Eye Toggle Icon Button -->
                        <button type="button" 
                                @click="showPass = !showPass" 
                                aria-label="Toggle password visibility"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-amber-300/70 hover:text-amber-300 focus:outline-none transition-colors">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg x-show="showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    @if ($errors->has('password'))
                        <p class="text-xs font-semibold text-rose-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first('password') }}</span>
                        </p>
                    @endif
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-xs text-zinc-300 pt-1">
                    <label class="inline-flex items-center cursor-pointer select-none hover:text-white transition-colors">
                        <input type="checkbox" name="remember" class="rounded border-amber-400/40 bg-black/40 text-amber-500 focus:ring-amber-400/30 w-4 h-4 mr-2">
                        <span>Ingat saya</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-amber-300 hover:text-amber-200 font-medium transition-colors">
                            Lupa Password?
                        </a>
                    @endif
                </div>

                <!-- Glowing Golden Submit Button (Matching Mockup) -->
                <div class="pt-3">
                    <button type="submit"
                            class="w-full py-3.5 px-6 rounded-2xl font-black text-sm uppercase tracking-widest text-zinc-950 bg-gradient-to-r from-amber-300 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-orange-500 shadow-[0_0_25px_rgba(245,158,11,0.45)] hover:shadow-[0_0_35px_rgba(245,158,11,0.7)] transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 cursor-pointer">
                        <span>LOGIN</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </form>

        </div>

        <!-- Back to Welcome Page Link -->
        <div class="text-center mt-5">
            <a href="{{ url('/') }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-black/30 hover:bg-black/50 backdrop-blur-md border border-white/10 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

    </div>

</body>
</html>
