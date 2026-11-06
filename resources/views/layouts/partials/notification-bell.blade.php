{{-- Lonceng notifikasi di header: jumlah belum dibaca + notifikasi terbaru. Klik item = tandai dibaca & buka tujuannya.
     Panel memakai warna eksplisit, bukan bg-white: di mode gelap .bg-white dibuat transparan (resources/css/app.css). --}}
@php
    $bellUser = auth()->user();
    $bellUnread = $bellUser->unreadSystemNotifications()->count();
    $bellItems = $bellUser->systemNotifications()->published()
        ->orderByRaw('CASE WHEN is_read = 0 OR read_at IS NULL THEN 0 ELSE 1 END')
        ->latest('created_at')->latest('id')
        ->limit(6)->get();
    $bellCanWrite = ! \App\Support\ReadOnlyAccess::applies($bellUser);
@endphp

<div class="relative shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
            class="relative p-1.5 sm:p-2 rounded-xl bg-white/60 dark:bg-white/5 border border-zinc-200/70 dark:border-white/10 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-150 shadow-xs cursor-pointer"
            title="Notifikasi">
        <span class="sr-only">Notifikasi{{ $bellUnread ? " ($bellUnread belum dibaca)" : '' }}</span>
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($bellUnread > 0)
            <span data-bell-count class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold leading-[18px] text-center ring-2 ring-white dark:ring-zinc-900">{{ $bellUnread > 99 ? '99+' : $bellUnread }}</span>
        @endif
    </button>

    <div x-show="open" x-transition.origin.top.right style="display: none;"
         class="fixed sm:absolute left-2 right-2 sm:left-auto sm:right-0 top-16 sm:top-auto sm:mt-2 sm:w-96 z-50 rounded-2xl bg-[#ffffff] dark:bg-[#18181b] border border-zinc-200 dark:border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-white/10">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">Notifikasi</p>
            @if ($bellUnread > 0 && $bellCanWrite)
                <form method="POST" action="{{ route('system-notifications.mark-all-read') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs font-semibold text-teal-700 dark:text-teal-400 hover:underline cursor-pointer">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        <div class="max-h-[60vh] overflow-y-auto divide-y divide-zinc-100 dark:divide-white/5">
            @forelse ($bellItems as $item)
                <a href="{{ route('system-notifications.open', $item) }}"
                   class="flex gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors {{ $item->isUnread() ? 'bg-teal-50/60 dark:bg-teal-950/20' : '' }}">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $item->isUnread() ? 'bg-rose-500' : 'bg-transparent' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-zinc-900 dark:text-white">{{ $item->title }}</span>
                        <span class="block text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ $item->message }}</span>
                        <span class="block text-[10px] text-zinc-400 mt-0.5">{{ $item->created_at?->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">Belum ada notifikasi.</p>
            @endforelse
        </div>

        <a href="{{ route('system-notifications.index') }}" class="block px-4 py-2.5 text-center text-xs font-semibold text-teal-700 dark:text-teal-400 border-t border-zinc-100 dark:border-white/10 hover:bg-zinc-50 dark:hover:bg-white/5">
            Lihat semua notifikasi
        </a>
    </div>
</div>
