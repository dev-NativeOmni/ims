@php
    $user = auth()->user();

    $hasRole = function (string $role) use ($user): bool {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return ($user->role?->name ?? null) === $role;
    };

    $isSuperAdmin = $hasRole('super_admin');
    $isAdminUser = $hasRole('admin');
    $isTeacher = $hasRole('teacher');
    $isParent = $hasRole('parent');
    $isStudent = $hasRole('student');
    $isSupervisor = $hasRole('supervisor');

    $isAdmin = $isSuperAdmin || $isAdminUser;
    $canManageRecords = $isSuperAdmin || $isAdminUser || $isTeacher || $isSupervisor;
    $canViewProgress = $isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor;
    $canViewReports = $isSuperAdmin || $isAdminUser || $isTeacher;
    $canViewAudit = $isSuperAdmin || $isAdminUser;

    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $unreadNotificationCount = 0;

    if ($user && method_exists($user, 'unreadSystemNotifications')) {
        $unreadNotificationCount = $user->unreadSystemNotifications()->count();
    }

    $getLinkClasses = function (bool $active): string {
        return $active
            ? 'flex items-center px-3 py-2 text-sm font-semibold rounded-lg bg-indigo-50 dark:bg-white/10 text-indigo-600 dark:text-white border border-indigo-100 dark:border-white/10 group transition-all duration-150 shadow-[0_4px_12px_rgba(99,102,241,0.08)] dark:shadow-[0_4px_12px_rgba(99,102,241,0.12)]'
            : 'flex items-center px-3 py-2 text-sm font-medium rounded-lg text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100/50 dark:hover:bg-white/5 hover:text-zinc-900 dark:hover:text-white border border-transparent hover:border-zinc-200 dark:hover:border-white/5 group transition-all duration-150';
    };

    $getIconClasses = function (bool $active): string {
        return $active
            ? 'mr-3 h-5 w-5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 transition-colors duration-150'
            : 'mr-3 h-5 w-5 text-zinc-400 dark:text-zinc-500 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 flex-shrink-0 transition-colors duration-150';
    };
@endphp

<!-- Mobile Sidebar (Drawer Overlay) -->
<div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" role="dialog" aria-modal="true" style="display: none;">
    <!-- Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-[#09090b]/80 backdrop-blur-sm" aria-hidden="true"></div>

    <!-- Sidebar Drawer Panel -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-[#09090b]/95 backdrop-blur-xl border-r border-white/5 shadow-xl">
         
         <!-- Close Button -->
         <div class="absolute top-0 right-0 -mr-12 pt-2">
             <button type="button" @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none">
                 <span class="sr-only">Tutup sidebar</span>
                 <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

         <!-- Mobile Logo -->
         <div class="flex-shrink-0 flex items-center px-4">
             <span class="font-bold text-xl text-white tracking-tight flex items-center gap-2">
                 <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                 </svg>
                 <span>HafizPlus</span>
             </span>
         </div>

         <!-- Mobile Menu List -->
         <div class="mt-5 flex-1 h-0 overflow-y-auto">
             <nav class="px-2 space-y-1">
                 @include('layouts.navigation-links')
             </nav>
         </div>

         <!-- Mobile Profile Footer -->
         <div class="flex-shrink-0 flex border-t border-white/5 p-4 bg-[#09090b]/40">
             <div class="flex items-center w-full">
                 <div class="flex-shrink-0">
                     @if ($user?->avatar)
                         <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shadow-inner border border-white/10">
                     @else
                         <div class="w-9 h-9 rounded-full bg-indigo-900/40 text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-bold text-sm shadow-inner uppercase">
                             {{ substr($user?->name ?? 'U', 0, 1) }}
                         </div>
                     @endif
                 </div>
                 <div class="ml-3 flex-1 min-w-0">
                     <p class="text-sm font-semibold text-zinc-200 truncate">
                         {{ $user?->name }}
                     </p>
                     <p class="text-xs text-zinc-400 truncate">
                         {{ $user?->role?->display_name ?? $user?->role?->name ?? '-' }}
                     </p>
                 </div>
                 <div class="ml-2 flex items-center gap-1">
                     @if ($hasRoute('profile.edit'))
                         <a href="{{ route('profile.edit') }}" class="p-1.5 rounded-full text-zinc-400 hover:text-zinc-200 hover:bg-white/5 focus:outline-none" title="Profil">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                             </svg>
                         </a>
                     @endif
                     <form method="POST" action="{{ route('logout') }}" class="inline">
                         @csrf
                         <button type="submit" class="p-1.5 rounded-full text-zinc-400 hover:text-red-400 hover:bg-red-500/10 focus:outline-none" title="Keluar">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                             </svg>
                         </button>
                     </form>
                 </div>
             </div>
         </div>
    </div>
    <!-- Dummy spacer to prevent overlay from closing immediately on tap close to edges -->
    <div class="flex-shrink-0 w-14"></div>
</div>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex md:w-56 md:flex-col md:fixed md:inset-y-0 z-20 bg-white/75 dark:bg-[#09090b]/60 backdrop-blur-xl border-r border-zinc-200 dark:border-white/5 transition-colors duration-200">
    <!-- Desktop Logo & Theme Toggle -->
    <div class="flex-shrink-0 flex items-center justify-between px-4 h-16 border-b border-zinc-200/50 dark:border-white/5 bg-zinc-50/50 dark:bg-[#09090b]/40 transition-colors duration-200">
        <a href="{{ route('dashboard') }}" class="font-bold text-lg text-zinc-800 dark:text-white tracking-tight flex items-center gap-1.5 shrink-0">
            <svg class="h-5.5 w-5.5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>HafizPlus</span>
        </a>

        <!-- Theme Toggle Button -->
        <button @click="toggleTheme()" class="p-1.5 rounded-lg bg-zinc-100 hover:bg-zinc-200/60 dark:bg-white/5 border border-zinc-200 dark:border-white/10 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-150" title="Ubah Tema">
            <svg x-show="dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
            </svg>
            <svg x-show="!dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>

    <!-- Desktop Menu List -->
    <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
        <nav class="flex-1 px-4 space-y-1">
            @include('layouts.navigation-links')
        </nav>
    </div>

    <!-- Desktop Profile Footer -->
    <div class="flex-shrink-0 flex border-t border-zinc-200 dark:border-white/5 p-4 bg-zinc-50/50 dark:bg-[#09090b]/40 transition-colors duration-200">
        <div class="flex items-center w-full">
            <div class="flex-shrink-0">
                @if ($user?->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shadow-inner border border-zinc-200 dark:border-white/10">
                @else
                    <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center font-bold text-sm shadow-inner uppercase">
                        {{ substr($user?->name ?? 'U', 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate">
                    {{ $user?->name }}
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                    {{ $user?->role?->display_name ?? $user?->role?->name ?? '-' }}
                </p>
            </div>
            <div class="ml-2 flex items-center gap-1">
                @if ($hasRoute('profile.edit'))
                    <a href="{{ route('profile.edit') }}" class="p-1.5 rounded-full text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-white/5 focus:outline-none" title="Profil">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="p-1.5 rounded-full text-zinc-400 hover:text-red-650 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 focus:outline-none" title="Keluar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Top Bar -->
<div class="sticky top-0 z-10 md:hidden flex h-16 bg-white/85 dark:bg-[#09090b]/60 backdrop-blur-xl border-b border-zinc-200 dark:border-white/5 flex-shrink-0 transition-colors duration-200">
    <button type="button" @click="sidebarOpen = true" class="px-4 border-r border-zinc-200 dark:border-white/5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none">
        <span class="sr-only">Buka sidebar</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <div class="flex-1 flex justify-between px-4 items-center">
        <div class="flex items-center gap-3">
            <span class="font-bold text-lg text-zinc-800 dark:text-white tracking-tight flex items-center gap-1.5">
                <svg class="h-5 w-5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>HafizPlus</span>
            </span>

            <!-- Mobile Theme Toggle -->
            <button @click="toggleTheme()" class="p-1.5 rounded-lg bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-150" title="Ubah Tema">
                <svg x-show="dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <svg x-show="!dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <!-- Small visual avatar on the right -->
        @if ($user?->avatar)
            <button type="button" @click="sidebarOpen = true" class="w-8 h-8 rounded-full overflow-hidden border border-zinc-200 dark:border-white/10 shadow-inner">
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
            </button>
        @else
            <button type="button" @click="sidebarOpen = true" class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center font-bold text-xs uppercase shadow-inner">
                {{ substr($user?->name ?? 'U', 0, 1) }}
            </button>
        @endif
    </div>
</div>