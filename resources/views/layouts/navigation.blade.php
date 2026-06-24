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

    $isAdmin = $isSuperAdmin || $isAdminUser;
    $canManageRecords = $isSuperAdmin || $isAdminUser || $isTeacher;
    $canViewProgress = $isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent;
    $canViewReports = $isSuperAdmin || $isAdminUser || $isTeacher;
    $canViewAudit = $isSuperAdmin || $isAdminUser;

    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $unreadNotificationCount = 0;

    if ($user && method_exists($user, 'unreadSystemNotifications')) {
        $unreadNotificationCount = $user->unreadSystemNotifications()->count();
    }

    $getLinkClasses = function (bool $active): string {
        return $active
            ? 'flex items-center px-3 py-2 text-sm font-semibold rounded-lg bg-indigo-50 text-indigo-700 group transition-all duration-150 shadow-sm'
            : 'flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 group transition-all duration-150';
    };

    $getIconClasses = function (bool $active): string {
        return $active
            ? 'mr-3 h-5 w-5 text-indigo-600 flex-shrink-0 transition-colors duration-150'
            : 'mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 flex-shrink-0 transition-colors duration-150';
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
         class="fixed inset-0 bg-gray-600 bg-opacity-75" aria-hidden="true"></div>

    <!-- Sidebar Drawer Panel -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-white shadow-xl">
         
         <!-- Close Button -->
         <div class="absolute top-0 right-0 -mr-12 pt-2">
             <button type="button" @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                 <span class="sr-only">Tutup sidebar</span>
                 <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

         <!-- Mobile Logo -->
         <div class="flex-shrink-0 flex items-center px-4">
             <span class="font-bold text-xl text-gray-800 tracking-tight flex items-center gap-2">
                 <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
         <div class="flex-shrink-0 flex border-t border-gray-200 p-4 bg-gray-50/50">
             <div class="flex items-center w-full">
                 <div class="flex-shrink-0">
                     @if ($user?->avatar)
                         <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shadow-inner">
                     @else
                         <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shadow-inner uppercase">
                             {{ substr($user?->name ?? 'U', 0, 1) }}
                         </div>
                     @endif
                 </div>
                 <div class="ml-3 flex-1 min-w-0">
                     <p class="text-sm font-semibold text-gray-800 truncate">
                         {{ $user?->name }}
                     </p>
                     <p class="text-xs text-gray-500 truncate">
                         {{ $user?->role?->display_name ?? $user?->role?->name ?? '-' }}
                     </p>
                 </div>
                 <div class="ml-2 flex items-center gap-1">
                     @if ($hasRoute('profile.edit'))
                         <a href="{{ route('profile.edit') }}" class="p-1.5 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none" title="Profil">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                             </svg>
                         </a>
                     @endif
                     <form method="POST" action="{{ route('logout') }}" class="inline">
                         @csrf
                         <button type="submit" class="p-1.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 focus:outline-none" title="Keluar">
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
<aside class="hidden md:flex md:w-56 md:flex-col md:fixed md:inset-y-0 z-20 bg-white border-r border-gray-200">
    <!-- Desktop Logo -->
    <div class="flex-shrink-0 flex items-center px-6 h-16 border-b border-gray-100 bg-white">
        <a href="{{ route('dashboard') }}" class="font-bold text-xl text-gray-800 tracking-tight flex items-center gap-2">
            <svg class="h-6 w-6 text-indigo-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>HafizPlus</span>
        </a>
    </div>

    <!-- Desktop Menu List -->
    <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
        <nav class="flex-1 px-4 space-y-1 bg-white">
            @include('layouts.navigation-links')
        </nav>
    </div>

    <!-- Desktop Profile Footer -->
    <div class="flex-shrink-0 flex border-t border-gray-200 p-4 bg-gray-50/50">
        <div class="flex items-center w-full">
            <div class="flex-shrink-0">
                @if ($user?->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shadow-inner">
                @else
                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shadow-inner uppercase">
                        {{ substr($user?->name ?? 'U', 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">
                    {{ $user?->name }}
                </p>
                <p class="text-xs text-gray-500 truncate">
                    {{ $user?->role?->display_name ?? $user?->role?->name ?? '-' }}
                </p>
            </div>
            <div class="ml-2 flex items-center gap-1">
                @if ($hasRoute('profile.edit'))
                    <a href="{{ route('profile.edit') }}" class="p-1.5 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none" title="Profil">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="p-1.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 focus:outline-none" title="Keluar">
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
<div class="sticky top-0 z-10 md:hidden flex h-16 bg-white border-b border-gray-200 flex-shrink-0">
    <button type="button" @click="sidebarOpen = true" class="px-4 border-r border-gray-200 text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
        <span class="sr-only">Buka sidebar</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <div class="flex-1 flex justify-between px-4 items-center bg-white">
        <span class="font-bold text-lg text-gray-800 tracking-tight flex items-center gap-2">
            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span>HafizPlus</span>
        </span>
        <!-- Small visual avatar on the right, clicking opens/toggles sidebar too -->
        @if ($user?->avatar)
            <button type="button" @click="sidebarOpen = true" class="w-8 h-8 rounded-full overflow-hidden shadow-inner">
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
            </button>
        @else
            <button type="button" @click="sidebarOpen = true" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs uppercase shadow-inner">
                {{ substr($user?->name ?? 'U', 0, 1) }}
            </button>
        @endif
    </div>
</div>