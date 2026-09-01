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
    $isHeadmaster = $hasRole('headmaster');
    $isTanse = $hasRole('tanse');
    $isCoordinatorTahfizh = $hasRole('coordinator_tahfizh');
    $isPendampingAdab = $hasRole('pendamping_adab');

    $isAdmin = $isSuperAdmin || $isAdminUser;

    $logo = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::get('logo') : null;
    $namaInstansi = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::get('nama_instansi') : null;

    $logoUrl = null;
    if ($logo) {
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            $logoUrl = $logo;
        } elseif (str_starts_with($logo, 'storage/')) {
            $logoUrl = asset($logo);
        } else {
            $logoUrl = asset('storage/' . $logo);
        }
    }

    $isPureTahfizhCoordinator = $isCoordinatorTahfizh && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;
    $isPureAdabCoordinator = $isPendampingAdab && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;
    $isPureTanseCoordinator = $isTanse && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;

    $canViewTahfizhGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isHeadmaster || $isCoordinatorTahfizh) && ! $isPureAdabCoordinator && ! $isPureTanseCoordinator;
    $canViewAdabGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isHeadmaster || $isPendampingAdab) && ! $isPureTahfizhCoordinator && ! $isPureTanseCoordinator;
    $canViewTanseGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isHeadmaster || $isTanse) && ! $isPureTahfizhCoordinator && ! $isPureAdabCoordinator;

    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $unreadNotificationCount = 0;

    if ($user && method_exists($user, 'unreadSystemNotifications')) {
        $unreadNotificationCount = $user->unreadSystemNotifications()->count();
    }

    $getLinkClasses = function (bool $active): string {
        return $active
            ? 'flex items-center px-3.5 py-2.5 text-sm font-bold rounded-2xl bg-white dark:bg-white/15 text-zinc-900 dark:text-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-white/80 dark:border-white/20 group transition-all duration-200'
            : 'flex items-center px-3.5 py-2.5 text-sm font-medium rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-white/40 dark:hover:bg-white/10 hover:text-zinc-900 dark:hover:text-white group transition-all duration-150';
    };

    $getIconClasses = function (bool $active): string {
        return $active
            ? 'mr-3 h-5 w-5 text-zinc-900 dark:text-white flex-shrink-0 transition-colors duration-150'
            : 'mr-3 h-5 w-5 text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-800 dark:group-hover:text-zinc-200 flex-shrink-0 transition-colors duration-150';
    };
@endphp

<!-- Global Sidebar (Floating Glass Drawer Overlay) -->
<div x-show="sidebarOpen" class="fixed inset-0 flex z-40" role="dialog" aria-modal="true" style="display: none;">
    <!-- Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-zinc-900/60 dark:bg-black/80 backdrop-blur-md" aria-hidden="true"></div>

    <!-- Floating Sidebar Drawer Panel (Monolith Style) -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full m-3 sm:m-4 rounded-3xl glass-sidebar p-4 shadow-2xl transition-all duration-200 border border-white/70 dark:border-white/10">
         
         <!-- Close Button -->
         <div class="absolute top-3 right-3">
             <button type="button" @click="sidebarOpen = false" class="glass-circle-btn w-8 h-8 focus:outline-none" title="Tutup">
                 <span class="sr-only">Tutup sidebar</span>
                 <svg class="h-4 w-4 text-zinc-600 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

         <!-- Brand Logo & Header in Sidebar -->
         <div class="flex-shrink-0 flex items-center gap-3 px-2 pt-2 pb-4 border-b border-white/40 dark:border-white/10">
              @if ($logoUrl)
                  <img src="{{ $logoUrl }}" class="h-9 w-auto max-w-[130px] object-contain flex-shrink-0 drop-shadow-sm" alt="{{ $namaInstansi ?? 'Logo' }}">
              @else
                  <div class="h-9 w-9 rounded-2xl bg-teal-500/20 text-teal-700 dark:text-teal-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60">
                      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                  </div>
              @endif
              <div class="min-w-0 pr-8">
                  <h1 class="font-extrabold text-sm sm:text-base text-zinc-900 dark:text-white leading-tight tracking-tight">
                      {{ $namaInstansi ?: 'IMS Tahfizh' }}
                  </h1>
                  <span class="text-[10px] font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider block mt-0.5">
                      Integrated System
                  </span>
              </div>
         </div>

         <!-- Menu Navigation List -->
         <div class="mt-4 flex-1 h-0 overflow-y-auto pr-1">
             <nav class="space-y-1">
                 @include('layouts.navigation-links')
             </nav>
         </div>

         <!-- Profile Footer Card (Frosted Pill Style) -->
         <div class="flex-shrink-0 mt-3 pt-3 border-t border-white/40 dark:border-white/10">
             <div class="p-3 rounded-2xl bg-white/60 dark:bg-white/5 border border-white/70 dark:border-white/10 shadow-sm flex items-center gap-3">
                 <div class="flex-shrink-0">
                     @if ($user?->avatar)
                         <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shadow-inner border border-white">
                     @else
                         <div class="w-9 h-9 rounded-full bg-emerald-600 dark:bg-emerald-500 text-white flex items-center justify-center font-bold text-xs uppercase shadow-inner flex-shrink-0 border border-white/80">
                             {{ substr($user?->name ?? 'U', 0, 1) }}
                         </div>
                     @endif
                 </div>
                 <div class="min-w-0 flex-1">
                     <p class="text-xs font-bold text-zinc-900 dark:text-white truncate leading-tight">
                         {{ $user?->name }}
                     </p>
                     <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 truncate leading-tight mt-0.5">
                         {{ $user?->role?->display_name ?? $user?->role?->name ?? '-' }}
                     </p>
                 </div>
             </div>

             <div class="flex items-center gap-1.5 mt-2">
                 @if ($hasRoute('profile.edit'))
                     <a href="{{ route('profile.edit') }}" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-white/40 dark:bg-white/5 hover:bg-white/80 dark:hover:bg-white/15 border border-white/60 dark:border-white/10 transition-all duration-150">
                         <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                         </svg>
                         Profil
                     </a>
                 @endif
                 <form method="POST" action="{{ route('logout') }}" class="flex-1">
                     @csrf
                     <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold text-rose-700 dark:text-rose-400 bg-rose-50/60 dark:bg-rose-950/30 hover:bg-rose-100/80 dark:hover:bg-rose-900/40 border border-rose-200/60 dark:border-rose-800/40 transition-all duration-150 cursor-pointer">
                         <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                         </svg>
                         Keluar
                     </button>
                 </form>
             </div>
         </div>
    </div>
    <div class="flex-shrink-0 w-14"></div>
</div>

<!-- Global Top Bar (Floating Pill Glass Navbar) -->
<header class="sticky top-3 z-30 mx-3 sm:mx-6 lg:mx-8 mb-4 transition-all duration-300">
    <div class="glass-topbar px-4 py-2.5 flex items-center justify-between gap-4">
        <!-- Left: Hamburger Button & Full Institution Brand -->
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" @click="sidebarOpen = true" class="glass-circle-btn flex-shrink-0" title="Menu Navigasi">
                <span class="sr-only">Buka sidebar</span>
                <svg class="h-5 w-5 text-zinc-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="flex items-center gap-2.5 min-w-0">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" class="h-7 sm:h-8 w-auto max-w-[120px] object-contain flex-shrink-0 drop-shadow-sm" alt="{{ $namaInstansi ?? 'Logo' }}">
                @else
                    <div class="h-8 w-8 rounded-full bg-teal-500/20 text-teal-800 dark:text-teal-300 flex items-center justify-center flex-shrink-0 shadow-sm border border-white/60">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                @endif
                <span class="font-extrabold text-sm sm:text-base md:text-lg text-zinc-900 dark:text-white tracking-tight leading-tight">
                    {{ $namaInstansi ?: 'IMS Tahfizh' }}
                </span>
            </div>
        </div>

        <!-- Right: Action Buttons (Theme Toggle & User Profile Avatar) -->
        <div class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0">
            <!-- Theme Toggle Button -->
            <button type="button" @click="toggleTheme()" class="glass-circle-btn" title="Ubah Tema">
                <svg x-show="dark" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <svg x-show="!dark" class="w-4 h-4 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            <!-- Avatar Profile Button -->
            @if ($user?->avatar)
                <button type="button" @click="sidebarOpen = true" class="w-10 h-10 rounded-full overflow-hidden border-2 border-white/80 dark:border-white/20 shadow-md hover:scale-105 transition cursor-pointer flex-shrink-0" title="{{ $user->name }}">
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                </button>
            @else
                <button type="button" @click="sidebarOpen = true" class="w-10 h-10 rounded-full bg-emerald-600 dark:bg-emerald-500 text-white font-bold text-sm uppercase shadow-md hover:scale-105 transition cursor-pointer flex items-center justify-center flex-shrink-0 border-2 border-white/80 dark:border-white/20" title="{{ $user?->name }}">
                    {{ substr($user?->name ?? 'U', 0, 1) }}
                </button>
            @endif
        </div>
    </div>
</header>