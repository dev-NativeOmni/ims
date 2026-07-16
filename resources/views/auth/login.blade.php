@php
    $logo = Schema::hasTable('settings') ? \App\Models\Setting::get('logo') : null;
    $namaInstansi = Schema::hasTable('settings') ? \App\Models\Setting::get('nama_instansi') : null;
    $loginBg = Schema::hasTable('settings') ? \App\Models\Setting::get('login_bg') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IMS') }} - {{ __('Log in') }}</title>

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;605;700&family=Outfit:wght@500;605;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & JS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Customizable CSS Theme Variables -->
    <style>
        :root {
            /* Full-screen background gradient colors */
            --login-bg-from: #f8fafc; /* Slate 50 */
            --login-bg-to: #e2e8f0;   /* Slate 200 */
            
            /* Branding colors */
            --theme-primary: #4f46e5;      /* Indigo 600 */
            --theme-primary-hover: #4338ca;/* Indigo 700 */
            --theme-primary-glow: rgba(79, 70, 229, 0.15);
            
            /* Glassmorphic card design customization */
            --card-bg: rgba(255, 255, 255, 0.8);
            --card-radius: 1.75rem; /* 28px */
            --card-border: rgba(148, 163, 184, 0.15);
            --card-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 50px 0 rgba(79, 70, 229, 0.02);

            /* Typography */
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        /* Frosted Glassmorphism Card Layout */
        .glass-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        body {
            font-family: var(--font-body);
            @if ($loginBg)
                background: linear-gradient(rgba(255, 255, 255, 0.85), rgba(248, 250, 252, 0.85)), url("{{ asset('storage/' . $loginBg) }}");
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
                background-repeat: no-repeat;
            @else
                background: linear-gradient(135deg, var(--login-bg-from) 0%, var(--login-bg-to) 100%);
            @endif
        }

        .font-display {
            font-family: var(--font-display);
        }

        /* Glassmorphic Premium Input */
        .premium-input {
            background-color: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #0f172a;
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .premium-input::placeholder {
            color: rgba(71, 85, 105, 0.5);
        }
        .premium-input:focus {
            background-color: #ffffff;
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 4px var(--theme-primary-glow);
            color: #0f172a;
            outline: none;
        }

        /* Premium button hover/active effects */
        .btn-primary-custom {
            background-color: var(--theme-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary-custom:hover {
            background-color: var(--theme-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -10px rgba(79, 70, 229, 0.4);
        }
        .btn-primary-custom:active {
            transform: translateY(1px);
        }
        
        .text-gradient-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <!-- Glow background blobs -->
    @if (!$loginBg)
        <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-indigo-200/40 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-purple-200/30 blur-3xl"></div>
        </div>
    @endif

    <!-- Login Container -->
    <div class="w-full max-w-md z-10">

        <!-- App Logo & Welcome Title -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/60 backdrop-blur-md border border-slate-200/50 shadow-sm mb-4 p-2 transition-transform duration-300 hover:scale-105">
                @if ($logo)
                    <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="w-12 h-12 object-contain" />
                @else
                    <!-- Default SVG Logo -->
                    <svg class="h-10 w-10 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                @endif
            </div>
            <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight leading-none">
                IMS <span class="text-gradient-primary">System</span>
            </h1>
            @if ($namaInstansi)
                <p class="text-indigo-600 text-xs mt-2 font-bold tracking-wide uppercase">{{ $namaInstansi }}</p>
                <p class="text-slate-500 text-[10px] mt-1 font-semibold tracking-wider">SISTEM MONITORING TAHFIDZ AL-QUR'AN</p>
            @else
                <p class="text-indigo-600/80 text-xs mt-2 font-semibold tracking-wider">SISTEM MONITORING TAHFIDZ AL-QUR'AN</p>
            @endif
        </div>

        <!-- Glassmorphism Login Card -->
        <div class="glass-card p-8 sm:p-10 relative">

            <h2 class="text-lg font-bold font-display text-slate-800 mb-6 text-center tracking-wide">Masuk ke Akun Anda</h2>

            <!-- Session Status Alert -->
            @if (session('status'))
                <div class="mb-5 text-center text-sm font-semibold text-indigo-700 bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Username Input Group -->
                <div class="space-y-2">
                    <label for="username" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">
                        {{ __('Username') }}
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </span>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Ketik username Anda"
                            class="premium-input block w-full pl-11 pr-4 py-3 text-sm focus:outline-none"
                        />
                    </div>
                    @if ($errors->has('username'))
                        <div class="text-xs font-medium text-rose-600 flex items-center gap-1.5 mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>{{ $errors->first('username') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Password Input Group -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label for="password" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">
                            {{ __('Password') }}
                        </label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Ketik password Anda"
                            class="premium-input block w-full pl-11 pr-4 py-3 text-sm focus:outline-none"
                        />
                    </div>
                    @if ($errors->has('password'))
                        <div class="text-xs font-medium text-rose-600 flex items-center gap-1.5 mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>{{ $errors->first('password') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer text-slate-500 select-none hover:text-slate-800 transition-colors duration-150">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 bg-white text-indigo-650 focus:ring-indigo-500 focus:outline-none w-4 h-4 mr-2"
                        />
                        <span>Ingat saya</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-semibold text-indigo-600 hover:text-indigo-750 transition-colors duration-150">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="btn-primary-custom w-full py-3.5 px-4 rounded-xl text-white font-bold text-sm shadow-lg focus:outline-none cursor-pointer flex justify-center items-center gap-2"
                    >
                        <span>Masuk Aplikasi</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </form>

        </div>

        <!-- Back to Welcome Page Link -->
        <div class="text-center mt-6">
            <a href="{{ url('/') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-850 transition-colors duration-150 inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

    </div>

</body>
</html>
