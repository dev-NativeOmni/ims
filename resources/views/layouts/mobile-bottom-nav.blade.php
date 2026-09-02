@php
    $user = auth()->user();
    $currentRoleName = $user?->currentRole()?->name ?? $user?->role?->name;

    $routeIs = fn($patterns) => request()->routeIs($patterns);

    // Build role-specific quick navigation items (Max 4 items + 1 drawer toggle)
    $navItems = [];

    switch ($currentRoleName) {
        case 'teacher':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
                [
                    'label' => 'Hafalan',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Spreadsheet',
                    'route' => 'spreadsheet-input.index',
                    'active' => $routeIs('spreadsheet-input.*'),
                    'icon' => 'table',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            break;

        case 'parent':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'student':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'coordinator_tahfizh':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Progress',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'chart',
                ],
            ];
            break;

        case 'supervisor':
        case 'pendamping_adab':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Materi',
                    'route' => 'adab-materials.index',
                    'active' => $routeIs('adab-materials.*'),
                    'icon' => 'book-open',
                ],
            ];
            break;

        case 'headmaster':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Tanse',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.chart'),
                    'icon' => 'star',
                ],
            ];
            break;

        case 'tanse':
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
                [
                    'label' => 'Disiplin',
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
                [
                    'label' => 'Notifikasi',
                    'route' => 'system-notifications.index',
                    'active' => $routeIs('system-notifications.*'),
                    'icon' => 'bell',
                ],
            ];
            break;

        default: // super_admin, admin
            $navItems = [
                [
                    'label' => 'Beranda',
                    'route' => 'dashboard',
                    'active' => $routeIs('dashboard') || $routeIs('*.dashboard'),
                    'icon' => 'home',
                ],
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
                [
                    'label' => 'Grafik',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
            ];
            break;
    }
@endphp

<!-- Bottom Floating Navigation Bar for Mobile Phones -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-xl border-t border-zinc-200/80 dark:border-white/10 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.4)] transition-all duration-200 pb-[env(safe-area-inset-bottom)]" aria-label="Navigasi Bawah">
    <div class="grid grid-cols-5 h-16 items-center px-1 max-w-lg mx-auto">
        @foreach ($navItems as $item)
            @if (\Illuminate\Support\Facades\Route::has($item['route']))
                <a href="{{ route($item['route']) }}"
                   class="relative flex flex-col items-center justify-center h-full py-1 text-center group select-none transition-all duration-150 {{ $item['active'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-400 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 font-medium' }}">
                    
                    <!-- Active Pill Indicator Top Glow -->
                    @if ($item['active'])
                        <span class="absolute top-0 w-8 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-b-full shadow-[0_2px_8px_rgba(79,70,229,0.6)]"></span>
                    @endif

                    <div class="p-1 rounded-xl transition-all duration-200 {{ $item['active'] ? 'bg-indigo-50 dark:bg-indigo-950/60 scale-105' : 'group-active:scale-95' }}">
                        @if ($item['icon'] === 'home')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        @elseif ($item['icon'] === 'book')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        @elseif ($item['icon'] === 'table')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        @elseif ($item['icon'] === 'shield')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        @elseif ($item['icon'] === 'quran')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        @elseif ($item['icon'] === 'chart')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        @elseif ($item['icon'] === 'academic')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @elseif ($item['icon'] === 'book-open')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        @elseif ($item['icon'] === 'star')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        @elseif ($item['icon'] === 'bell')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        @elseif ($item['icon'] === 'document')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @elseif ($item['icon'] === 'users')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.3' : '1.8' }}" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        @endif
                    </div>
                    <span class="text-[10px] mt-0.5 tracking-tight truncate max-w-[64px]">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach

        <!-- 5th Item: "Menu Lainnya" Drawer Trigger -->
        <button type="button"
                @click="sidebarOpen = true"
                class="relative flex flex-col items-center justify-center h-full py-1 text-center group select-none text-zinc-400 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium transition-all duration-150 cursor-pointer">
            <div class="p-1 rounded-xl transition-all duration-200 group-active:scale-95 group-hover:bg-zinc-100 dark:group-hover:bg-white/5">
                <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
            <span class="text-[10px] mt-0.5 tracking-tight">Lainnya</span>
        </button>
    </div>
</nav>
