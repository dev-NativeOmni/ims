<div x-data="{ status: '{{ $isActive ? '1' : '0' }}' }" class="bg-gray-50/50 dark:bg-zinc-850/30 border border-gray-200 dark:border-zinc-800 rounded-2xl p-3 shadow-xs transition duration-150 hover:shadow-sm w-[220px] shrink-0">
    <div class="flex flex-col gap-2">
        <div class="flex justify-between items-start">
            <div>
                <span class="font-bold text-xs text-gray-900 dark:text-white leading-tight block">
                    {{ $class->name }}
                </span>
                <span class="text-[9px] text-gray-450 dark:text-zinc-500 uppercase tracking-wide font-medium mt-0.5 block truncate max-w-[130px]" title="{{ $class->program?->name }}">
                    {{ $class->program?->name }}
                </span>
            </div>
            <!-- Indicator dot -->
            <span :class="status === '1' ? 'bg-emerald-500' : 'bg-rose-500'" class="w-2 h-2 rounded-full mt-1.5 shrink-0 transition"></span>
        </div>
        
        <!-- Toggle button group -->
        <div class="flex items-center bg-gray-100 dark:bg-zinc-800 rounded-xl p-0.5 border border-gray-200/60 dark:border-zinc-700 w-full justify-between">
            <label class="flex-1 cursor-pointer select-none">
                <input type="radio" name="schedules[{{ $dayNum }}][{{ $class->id }}]" value="1" x-model="status" class="sr-only">
                <span :class="status === '1' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-500 dark:text-zinc-400 hover:text-gray-700'" class="text-center py-1 rounded-lg text-[9px] font-extrabold block transition duration-150">
                    Aktif
                </span>
            </label>
            <label class="flex-1 cursor-pointer select-none">
                <input type="radio" name="schedules[{{ $dayNum }}][{{ $class->id }}]" value="0" x-model="status" class="sr-only">
                <span :class="status === '0' ? 'bg-rose-600 text-white shadow-xs' : 'text-gray-450 dark:text-zinc-500 hover:text-gray-700'" class="text-center py-1 rounded-lg text-[9px] font-extrabold block transition duration-150">
                    Non Aktif
                </span>
            </label>
        </div>
    </div>
</div>
