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
    $isLoggedIn = (bool) $user;

    $logo = \App\Models\Setting::get('logo');
    $namaInstansi = \App\Models\Setting::get('nama_instansi');

    $isPureTahfizhCoordinator = $isCoordinatorTahfizh && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;
    $isPureAdabCoordinator = $isPendampingAdab && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;
    $isPureTanseCoordinator = $isTanse && ! $isAdmin && ! $isHeadmaster && ! $isSupervisor && ! $isTeacher;

    $canViewTahfizhGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isHeadmaster || $isCoordinatorTahfizh) && ! $isPureAdabCoordinator && ! $isPureTanseCoordinator;
    $canViewAdabGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isPendampingAdab) && ! $isPureTahfizhCoordinator && ! $isPureTanseCoordinator;
    $canViewTanseGroup = ($isSuperAdmin || $isAdminUser || $isTeacher || $isParent || $isStudent || $isSupervisor || $isHeadmaster || $isTanse) && ! $isPureTahfizhCoordinator && ! $isPureAdabCoordinator;

    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $unreadNotificationCount = 0;

    if ($user && method_exists($user, 'unreadSystemNotifications')) {
        $unreadNotificationCount = $user->unreadSystemNotifications()->count();
    }

    $getLinkClasses = function (bool $active): string {
        return $active
            ? 'flex items-center px-3 py-2 text-sm font-semibold rounded-lg bg-indigo-50 dark:bg-white/10 text-indigo-600 dark:text-white border border-indigo-100 dark:border-white/10 group transition-all duration-150 shadow-[0_4px_12px_rgba(13,148,136,0.08)] dark:shadow-[0_4px_12px_rgba(13,148,136,0.12)]'
            : 'flex items-center px-3 py-2 text-sm font-medium rounded-lg text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100/50 dark:hover:bg-white/5 hover:text-zinc-900 dark:hover:text-white border border-transparent hover:border-zinc-200 dark:hover:border-white/5 group transition-all duration-150';
    };

    $getIconClasses = function (bool $active): string {
        return $active
            ? 'mr-3 h-5 w-5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 transition-colors duration-150'
            : 'mr-3 h-5 w-5 text-zinc-400 dark:text-zinc-500 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 flex-shrink-0 transition-colors duration-150';
    };
@endphp

<!-- Global Sidebar (Drawer Overlay) -->
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
         class="fixed inset-0 bg-zinc-900/60 dark:bg-[#09090b]/80 backdrop-blur-sm" aria-hidden="true"></div>

    <!-- Sidebar Drawer Panel -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-xl border-r border-zinc-200 dark:border-white/5 shadow-xl transition-colors duration-200">
         
         <!-- Close Button -->
         <div class="absolute top-0 right-0 -mr-12 pt-2">
             <button type="button" @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none">
                 <span class="sr-only">Tutup sidebar</span>
                 <svg class="h-6 w-6 text-zinc-500 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

         <!-- Logo -->
         <div class="flex-shrink-0 flex items-center px-4">
             <span class="font-bold text-xl text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                  @if ($logo)
                      <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/10 border border-zinc-200 dark:border-white/10 flex-shrink-0">
                          <img src="{{ asset('storage/' . $logo) }}" class="h-5 w-5 object-contain" alt="Logo">
                      </div>
                  @else
                      <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/10 border border-zinc-200 dark:border-white/10 flex-shrink-0 text-indigo-600 dark:text-indigo-400">
                          <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                          </svg>
                      </div>
                  @endif
                 <span class="truncate max-w-[160px]">{{ $namaInstansi ?: 'IMS' }}</span>
             </span>
         </div>

         <!-- Menu List -->
         <div class="mt-5 flex-1 h-0 overflow-y-auto">
             <nav class="px-2 space-y-1">
                 @include('layouts.navigation-links')
             </nav>
         </div>

         <!-- Profile Footer -->
         <div class="flex-shrink-0 border-t border-zinc-200 dark:border-white/5 p-3 bg-zinc-50/50 dark:bg-[#09090b]/40 transition-colors duration-200">
             <!-- Row 1: Avatar + Name + Role -->
             <div class="flex items-center gap-2.5 min-w-0">
                 <div class="flex-shrink-0">
                     @if ($user?->avatar)
                         <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover shadow-inner border border-zinc-200 dark:border-white/10">
                     @else
                         <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center font-bold text-xs shadow-inner uppercase flex-shrink-0">
                             {{ substr($user?->name ?? 'U', 0, 1) }}
                         </div>
                     @endif
                 </div>
                 <div class="min-w-0 flex-1">
                     <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate leading-tight">
                         {{ $user?->name }}
                     </p>
                     <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate leading-tight mt-0.5 font-bold">
                         {{ $user?->currentRole()?->display_name ?? $user?->role?->display_name ?? '-' }}
                     </p>
                 </div>
             </div>

             {{-- Multi-Role Switcher in Sidebar Drawer --}}
             @if ($user && $user->assignedRoles()->count() > 1)
                 <div class="mt-2 pt-2 border-t border-zinc-200/60 dark:border-white/5" x-data="{ switchOpen: false }">
                     <div class="flex items-center justify-between">
                         <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1">
                             <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                             </svg>
                             Ganti Peran:
                         </span>
                         <button type="button" @click="switchOpen = !switchOpen" class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-0.5 cursor-pointer">
                             <span x-text="switchOpen ? 'Tutup' : 'Pilih'"></span>
                             <svg class="w-3 h-3 transition-transform" :class="switchOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                             </svg>
                         </button>
                     </div>
                     <div x-show="switchOpen" class="mt-1.5 space-y-1" style="display: none;">
                         @foreach ($user->assignedRoles() as $ar)
                             @php $isCurrent = ($user->currentRole()?->id === $ar->id); @endphp
                             @if (! $isCurrent)
                                 <form method="POST" action="{{ route('role.switch') }}">
                                     @csrf
                                     <input type="hidden" name="role_id" value="{{ $ar->id }}">
                                     <button type="submit" class="w-full flex items-center justify-between px-2 py-1 rounded-md text-[11px] font-medium bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-indigo-50 dark:hover:bg-indigo-950 hover:text-indigo-600 dark:hover:text-indigo-300 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer">
                                         <span>{{ $ar->display_name }}</span>
                                         <span class="text-[9px] text-indigo-500 font-bold">Pilih →</span>
                                     </button>
                                 </form>
                             @else
                                 <div class="px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
                                     <span>{{ $ar->display_name }}</span>
                                     <span class="text-[9px]">✓ Aktif</span>
                                 </div>
                             @endif
                         @endforeach
                     </div>
                 </div>
             @endif
             <!-- Row 2: Action Buttons -->
             <div class="flex items-center gap-1 mt-2 pt-2 border-t border-zinc-200/60 dark:border-white/5">
                 @if ($hasRoute('profile.edit'))
                     <a href="{{ route('profile.edit') }}" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[10px] font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-white/5 border border-transparent hover:border-zinc-200 dark:hover:border-white/5 transition-all duration-150">
                         <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                         </svg>
                         Profil
                     </a>
                 @endif
                 <form method="POST" action="{{ route('logout') }}" class="flex-1">
                     @csrf
                     <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[10px] font-medium text-zinc-500 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-red-55/10 dark:hover:bg-red-500/10 border border-transparent hover:border-red-100 dark:hover:border-red-500/10 transition-all duration-150">
                         <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                         </svg>
                         Keluar
                     </button>
                 </form>
             </div>
         </div>
    </div>
    <!-- Dummy spacer to prevent overlay from closing immediately on tap close to edges -->
    <div class="flex-shrink-0 w-14"></div>
</div>

<!-- Global Top Bar -->
<div class="sticky top-0 z-30 flex h-16 bg-white/85 dark:bg-[#09090b]/60 backdrop-blur-xl border-b border-zinc-200 dark:border-white/5 flex-shrink-0 transition-colors duration-200">
    <button type="button" @click="sidebarOpen = true" class="px-4 border-r border-zinc-200 dark:border-white/5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none">
        <span class="sr-only">Buka sidebar</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <div class="flex-1 flex justify-between px-4 items-center">
        <div class="flex items-center gap-3">
            <span class="font-bold text-lg text-zinc-800 dark:text-white tracking-tight flex items-center gap-1.5">
                @if ($logo)
                    <div class="w-7 h-7 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 flex-shrink-0">
                        <img src="{{ asset('storage/' . $logo) }}" class="h-4.5 w-4.5 object-contain" alt="Logo">
                    </div>
                @else
                    <div class="w-7 h-7 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 flex-shrink-0 text-indigo-500 dark:text-indigo-400">
                        <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                @endif
                <span class="truncate max-w-[120px]">{{ $namaInstansi ?: 'IMS' }}</span>
            </span>

            <!-- Theme Toggle -->
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