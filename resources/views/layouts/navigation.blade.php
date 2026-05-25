@php
    $user = auth()->user();

    $isAdmin = $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
    $canManageRecords = $user?->hasAnyRole(['super_admin', 'admin', 'teacher']) ?? false;

    $hasNotificationsRoute = \Illuminate\Support\Facades\Route::has('system-notifications.index');

    $unreadNotificationCount = 0;

    if ($user && method_exists($user, 'unreadSystemNotifications')) {
        $unreadNotificationCount = $user->unreadSystemNotifications()->count();
    }
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-bold text-xl text-gray-800">
                        HafizPlus
                    </a>
                </div>

                <div class="hidden space-x-4 lg:space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('*.dashboard')">
                        Dashboard
                    </x-nav-link>

                    @if ($isAdmin)
                        <x-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.*')">
                            Program
                        </x-nav-link>

                        <x-nav-link :href="route('class-rooms.index')" :active="request()->routeIs('class-rooms.*')">
                            Kelas
                        </x-nav-link>

                        <x-nav-link :href="route('teachers.index')" :active="request()->routeIs('teachers.*')">
                            Guru
                        </x-nav-link>

                        <x-nav-link :href="route('parents.index')" :active="request()->routeIs('parents.*')">
                            Orangtua
                        </x-nav-link>

                        <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                            Santri
                        </x-nav-link>
                    @endif

                    @if ($canManageRecords)
                        <x-nav-link :href="route('quick-inputs.index')" :active="request()->routeIs('quick-inputs.*')">
                            Input Cepat
                        </x-nav-link>

                        <x-nav-link :href="route('hafalan-records.index')" :active="request()->routeIs('hafalan-records.*')">
                            Hafalan
                        </x-nav-link>

                        <x-nav-link :href="route('murajaah-records.index')" :active="request()->routeIs('murajaah-records.*')">
                            Murajaah
                        </x-nav-link>

                        <x-nav-link :href="route('hafalan-targets.index')" :active="request()->routeIs('hafalan-targets.*')">
                            Target
                        </x-nav-link>

                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            Laporan
                        </x-nav-link>
                    @endif

                    @if ($hasNotificationsRoute)
                        <x-nav-link :href="route('system-notifications.index')" :active="request()->routeIs('system-notifications.*')">
                            <span class="inline-flex items-center gap-1">
                                Notifikasi

                                @if ($unreadNotificationCount > 0)
                                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold text-white">
                                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </span>
                        </x-nav-link>
                    @endif

                    @if ($isAdmin)
                        <x-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">
                            Audit
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-600 bg-white hover:text-gray-800 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

                        @if ($hasNotificationsRoute)
                            <x-dropdown-link :href="route('system-notifications.index')">
                                Notifikasi
                                @if ($unreadNotificationCount > 0)
                                    ({{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }})
                                @endif
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }"
                              class="inline-flex"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }"
                              class="hidden"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('*.dashboard')">
                Dashboard
            </x-responsive-nav-link>

            @if ($isAdmin)
                <x-responsive-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.*')">
                    Program
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('class-rooms.index')" :active="request()->routeIs('class-rooms.*')">
                    Kelas
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('teachers.index')" :active="request()->routeIs('teachers.*')">
                    Guru
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('parents.index')" :active="request()->routeIs('parents.*')">
                    Orangtua
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                    Santri
                </x-responsive-nav-link>
            @endif

            @if ($canManageRecords)
                <x-responsive-nav-link :href="route('quick-inputs.index')" :active="request()->routeIs('quick-inputs.*')">
                    Input Cepat
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('hafalan-records.index')" :active="request()->routeIs('hafalan-records.*')">
                    Hafalan
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('murajaah-records.index')" :active="request()->routeIs('murajaah-records.*')">
                    Murajaah
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('hafalan-targets.index')" :active="request()->routeIs('hafalan-targets.*')">
                    Target
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    Laporan
                </x-responsive-nav-link>
            @endif

            @if ($hasNotificationsRoute)
                <x-responsive-nav-link :href="route('system-notifications.index')" :active="request()->routeIs('system-notifications.*')">
                    Notifikasi
                    @if ($unreadNotificationCount > 0)
                        ({{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }})
                    @endif
                </x-responsive-nav-link>
            @endif

            @if ($isAdmin)
                <x-responsive-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">
                    Audit
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">
                    {{ Auth::user()->name }}
                </div>
                <div class="font-medium text-sm text-gray-600">
                    {{ Auth::user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profil
                </x-responsive-nav-link>

                @if ($hasNotificationsRoute)
                    <x-responsive-nav-link :href="route('system-notifications.index')" :active="request()->routeIs('system-notifications.*')">
                        Notifikasi
                        @if ($unreadNotificationCount > 0)
                            ({{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }})
                        @endif
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                                           onclick="event.preventDefault(); this.closest('form').submit();">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>