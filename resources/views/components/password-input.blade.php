@props(['disabled' => false, 'placeholder' => '', 'showToggle' => true])

<div x-data="{ show: false }" class="w-full space-y-2">
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'border-zinc-300 dark:border-zinc-700 bg-transparent text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm w-full text-sm px-3.5 py-2.5']) }}
    />
    @if ($showToggle)
        <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
            <input type="checkbox" x-model="show" class="rounded border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
            <span>Tampilkan sandi</span>
        </label>
    @endif
</div>
