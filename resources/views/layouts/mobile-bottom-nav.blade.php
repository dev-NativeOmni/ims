@php
    $user = auth()->user();
    $currentRoleName = $user?->currentRole()?->name ?? $user?->role?->name;

    $routeIs = fn($patterns) => request()->routeIs($patterns);

    // Build role-specific left 2 items and right 1 item (+ 1 drawer "Lainnya" at far right)
    // Beranda/Dashboard is ALWAYS positioned in the center (Position 3) with prominent elevated styling!
    $leftItems = [];
    $rightItems = [];

    switch ($currentRoleName) {
        case 'teacher':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Input',
                    'route' => 'spreadsheet-input.index',
                    'active' => $routeIs('spreadsheet-input.*'),
                    'icon' => 'table',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            break;

        case 'parent':
            $leftItems = [
                [
                    'label' => 'Tahfizh',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'student':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'coordinator_tahfizh':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Progres',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'chart',
                ],
            ];
            break;

        case 'supervisor':
        case 'pendamping_adab':
            $leftItems = [
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.index') || $routeIs('adab.show'),
                    'icon' => 'shield',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'adab.chart',
                    'active' => $routeIs('adab.chart'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Materi',
                    'route' => 'adab-materials.index',
                    'active' => $routeIs('adab-materials.*'),
                    'icon' => 'book-open',
                ],
            ];
            break;

        case 'headmaster':
            $leftItems = [
                [
                    'label' => 'Tahfizh',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.chart',
                    'active' => $routeIs('adab.chart'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Tanse',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.chart'),
                    'icon' => 'star',
                ],
            ];
            break;

        case 'tanse':
            $leftItems = [
                [
                    'label' => 'Poin',
                    'route' => 'student-points.index',
                    'active' => $routeIs('student-points.*') && !$routeIs('student-points.chart'),
                    'icon' => 'star',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.chart'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Pesan',
                    'route' => 'system-notifications.index',
                    'active' => $routeIs('system-notifications.*'),
                    'icon' => 'bell',
                ],
            ];
            break;

        default: // super_admin, admin
            $leftItems = [
                [
                    'label' => 'Murid',
                    'route' => 'students.index',
                    'active' => $routeIs('students.*'),
                    'icon' => 'users',
                ],
                [
                    'label' => 'Tahfizh',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Grafik',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
            ];
            break;
    }

    $isDashboardActive = $routeIs('dashboard') || $routeIs('*.dashboard') || request()->is('*/dashboard*') || request()->is('dashboard');
@endphp

<!-- Bottom Floating Navigation Bar with Elevated Center Dashboard Button -->
<nav x-show="!sidebarOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="translate-y-full opacity-0"
     class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-xl border-t border-zinc-200/80 dark:border-white/10 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] dark:shadow-[0_-4px_25px_rgba(0,0,0,0.5)] transition-all duration-200 pb-[max(env(safe-area-inset-bottom),6px)]" aria-label="Navigasi Bawah">
    
    <div class="grid grid-cols-5 h-16 max-w-md mx-auto items-end px-1 relative">

        <!-- 1. LEFT ITEM 1 -->
        @if (isset($leftItems[0]) && \Illuminate\Support\Facades\Route::has($leftItems[0]['route']))
            <a href="{{ route($leftItems[0]['route']) }}"
               class="flex flex-col items-center justify-center pb-2 select-none group transition-colors duration-150 {{ $leftItems[0]['active'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $leftItems[0]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($leftItems[0]['icon'] === 'book')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'users')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'shield')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'star')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'chart')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @endif
                </div>
                <span class="text-[10px] tracking-tight leading-none mt-0.5 truncate max-w-full px-0.5">{{ $leftItems[0]['label'] }}</span>
            </a>
        @endif

        <!-- 2. LEFT ITEM 2 -->
        @if (isset($leftItems[1]) && \Illuminate\Support\Facades\Route::has($leftItems[1]['route']))
            <a href="{{ route($leftItems[1]['route']) }}"
               class="flex flex-col items-center justify-center pb-2 select-none group transition-colors duration-150 {{ $leftItems[1]['active'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $leftItems[1]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($leftItems[1]['icon'] === 'table')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'book')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'shield')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'chart')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @endif
                </div>
                <span class="text-[10px] tracking-tight leading-none mt-0.5 truncate max-w-full px-0.5">{{ $leftItems[1]['label'] }}</span>
            </a>
        @endif

        <!-- 3. CENTER ELEVATED BERANDA / DASHBOARD BUTTON (VIBRANT GLOWING SQUIRCLE) -->
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center select-none group relative -top-3.5 z-10">
            <!-- Vibrant Glowing Floating Squircle Button -->
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-300 group-active:scale-95 ring-4 ring-white dark:ring-[#09090b] shadow-[0_8px_20px_rgba(79,70,229,0.45)] dark:shadow-[0_8px_25px_rgba(99,102,241,0.5)] bg-gradient-to-tr from-indigo-600 via-indigo-600 to-indigo-500 text-white {{ $isDashboardActive ? 'scale-105 ring-indigo-200 dark:ring-indigo-900 shadow-[0_10px_25px_rgba(79,70,229,0.6)]' : 'hover:scale-105' }}">
                <!-- Solid 4-Square Grid Dashboard Icon (Sharp, Vibrant, High Visibility) -->
                <svg class="w-7 h-7 text-white drop-shadow-sm" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3.5" y="3.5" width="7" height="7" rx="2" />
                    <rect x="13.5" y="3.5" width="7" height="7" rx="2" />
                    <rect x="3.5" y="13.5" width="7" height="7" rx="2" />
                    <rect x="13.5" y="13.5" width="7" height="7" rx="2" />
                </svg>
            </div>
            <span class="text-[10.5px] font-extrabold tracking-tight leading-none mt-1 {{ $isDashboardActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-600 dark:text-zinc-300' }}">
                Dashboard
            </span>
        </a>

        <!-- 4. RIGHT ITEM 1 -->
        @if (isset($rightItems[0]) && \Illuminate\Support\Facades\Route::has($rightItems[0]['route']))
            <a href="{{ route($rightItems[0]['route']) }}"
               class="flex flex-col items-center justify-center pb-2 select-none group transition-colors duration-150 {{ $rightItems[0]['active'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $rightItems[0]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($rightItems[0]['icon'] === 'shield')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'quran')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'chart')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'book-open')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'star')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'bell')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    @endif
                </div>
                <span class="text-[10px] tracking-tight leading-none mt-0.5 truncate max-w-full px-0.5">{{ $rightItems[0]['label'] }}</span>
            </a>
        @endif

        <!-- 5. RIGHT ITEM 2: "Menu Lainnya" Drawer Trigger -->
        <button type="button"
                @click="sidebarOpen = true"
                class="flex flex-col items-center justify-center pb-2 select-none group transition-colors duration-150 text-zinc-500 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium cursor-pointer">
            <div class="p-1 rounded-xl transition-all duration-200 group-active:scale-95">
                <svg class="w-5 h-5 shrink-0 text-zinc-500 dark:text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
            <span class="text-[10px] tracking-tight leading-none mt-0.5">Lainnya</span>
        </button>

    </div>
</nav>
