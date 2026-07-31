@props(['disabled' => false, 'placeholder' => ''])

<div x-data="{ show: false }" class="relative w-full">
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'border-zinc-300 dark:border-zinc-700 bg-transparent text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm w-full pr-12 text-sm']) }}
    />
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-all focus:outline-none flex items-center justify-center"
        title="Tampilkan / Sembunyikan Password"
    >
        <!-- Eye Open Icon (when show is true) -->
        <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <!-- Eye Slashed Icon (when show is false) -->
        <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.015 10.015 0 014.122-.963c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
        </svg>
    </button>
</div>
