{{-- Data murid untuk akun ber-role Murid (UserController::store): kelas, guru pembimbing, orangtua lama/baru.
     Hanya tampil & dikirim bila role Murid dipilih (utama atau tambahan); butuh x-data isStudent dari form. --}}
@php
    $fieldClass = 'w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white';
    $oldStudent = old('student', []);
    $oldParentIds = array_map('strval', old('parent_ids', []));
    $oldRelations = old('parent_relations', []);
    $relationOptions = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'];
@endphp

<div x-show="isStudent" x-cloak class="rounded-xl border border-teal-200 dark:border-teal-500/30 p-4 bg-teal-50/40 dark:bg-teal-950/20 space-y-4"
     @unless ($studentRoleId && (old('role_id') == $studentRoleId || in_array((string) $studentRoleId, array_map('strval', old('additional_role_ids', [])), true))) style="display: none;" @endunless>
    <div>
        <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Data Murid</p>
        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
            Akun murid langsung dibuatkan data murid di kelasnya. Bila murid dengan nama yang sama sudah ada di kelas itu dan belum punya akun, akun ini disambungkan ke data tersebut (tidak dibuat ganda).
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="student_class_room_id" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Kelas <span class="text-rose-500">*</span></label>
            <select name="student[class_room_id]" id="student_class_room_id" :disabled="!isStudent" :required="isStudent" class="{{ $fieldClass }}">
                <option value="">-- Pilih Kelas --</option>
                @foreach ($classRooms as $classRoom)
                    <option value="{{ $classRoom->id }}" @selected(($oldStudent['class_room_id'] ?? null) == $classRoom->id) class="dark:bg-zinc-900">
                        {{ $classRoom->name }}{{ $classRoom->program ? ' — '.$classRoom->program->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('student.class_room_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="student_teacher_id" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Guru Pembimbing <span class="text-rose-500">*</span></label>
            <select name="student[teacher_id]" id="student_teacher_id" :disabled="!isStudent" :required="isStudent" class="{{ $fieldClass }}">
                <option value="">-- Pilih Guru --</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(($oldStudent['teacher_id'] ?? null) == $teacher->id) class="dark:bg-zinc-900">{{ $teacher->user?->name }}</option>
                @endforeach
            </select>
            @error('student.teacher_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="student_number" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Nomor Induk (opsional)</label>
            <input type="text" name="student[student_number]" id="student_number" value="{{ $oldStudent['student_number'] ?? '' }}" :disabled="!isStudent" class="{{ $fieldClass }}">
            @error('student.student_number') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="student_gender" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Jenis Kelamin (opsional)</label>
            <select name="student[gender]" id="student_gender" :disabled="!isStudent" class="{{ $fieldClass }}">
                <option value="">-- Pilih --</option>
                <option value="male" @selected(($oldStudent['gender'] ?? null) === 'male') class="dark:bg-zinc-900">Laki-laki</option>
                <option value="female" @selected(($oldStudent['gender'] ?? null) === 'female') class="dark:bg-zinc-900">Perempuan</option>
            </select>
        </div>
    </div>

    {{-- Orangtua yang sudah terdaftar --}}
    <div x-data="{ q: '', picked: @js($oldParentIds) }" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Orangtua/Wali yang sudah terdaftar</p>
            <span class="text-[11px] text-zinc-500" x-text="picked.length ? picked.length + ' dipilih' : ''"></span>
        </div>
        <input type="search" x-model="q" placeholder="Cari nama/username orangtua atau nama anak…" :disabled="!isStudent" class="{{ $fieldClass }}">
        <div class="max-h-56 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800 bg-white dark:bg-zinc-900">
            @forelse ($parents as $parent)
                @php
                    $search = mb_strtolower(trim(($parent->user?->name ?? '').' '.($parent->user?->username ?? '').' '.$parent->students->pluck('name')->join(' ')));
                @endphp
                <div x-show="picked.includes('{{ $parent->id }}') || ! q || @js($search).includes(q.toLowerCase())" class="flex flex-wrap items-center gap-2 px-3 py-2">
                    <label class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer">
                        <input type="checkbox" name="parent_ids[]" value="{{ $parent->id }}" x-model="picked" :disabled="!isStudent" class="rounded border-zinc-300 dark:border-zinc-600 text-teal-600 focus:ring-teal-500">
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ $parent->user?->name }} <span class="font-normal text-zinc-400">({{ $parent->user?->username }})</span></span>
                            @if ($parent->students->isNotEmpty())
                                <span class="block text-[10px] text-zinc-500 truncate">Anak: {{ $parent->students->pluck('name')->join(', ') }}</span>
                            @endif
                        </span>
                    </label>
                    <select name="parent_relations[{{ $parent->id }}]" x-show="picked.includes('{{ $parent->id }}')" :disabled="!isStudent || ! picked.includes('{{ $parent->id }}')" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                        <option value="">Hubungan</option>
                        @foreach ($relationOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($oldRelations[$parent->id] ?? null) === $value) class="dark:bg-zinc-900">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @empty
                <p class="px-3 py-3 text-xs text-zinc-500">Belum ada akun orangtua.</p>
            @endforelse
        </div>
        @error('parent_ids') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Orangtua baru: dibuatkan akun orangtua sekaligus --}}
    <div x-data="{ rows: @js(array_values(old('new_parents', []))) }" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Orangtua/Wali baru</p>
            <button type="button" @click="rows.push({ name: '', username: '', password: '', phone: '', relation: '' })" :disabled="!isStudent"
                    class="text-xs font-semibold text-teal-700 dark:text-teal-400 hover:underline cursor-pointer">+ Tambah orangtua baru</button>
        </div>
        <template x-for="(row, i) in rows" :key="i">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input type="text" :name="`new_parents[${i}][name]`" x-model="row.name" placeholder="Nama orangtua *" :disabled="!isStudent" :required="isStudent" class="{{ $fieldClass }}">
                <select :name="`new_parents[${i}][relation]`" x-model="row.relation" :disabled="!isStudent" class="{{ $fieldClass }}">
                    <option value="">Hubungan</option>
                    @foreach ($relationOptions as $value => $label)
                        <option value="{{ $value }}" class="dark:bg-zinc-900">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" :name="`new_parents[${i}][username]`" x-model="row.username" placeholder="Username login *" :disabled="!isStudent" :required="isStudent" class="{{ $fieldClass }}">
                <input type="text" :name="`new_parents[${i}][password]`" x-model="row.password" placeholder="Password (min. 6) *" :disabled="!isStudent" :required="isStudent" minlength="6" class="{{ $fieldClass }}">
                <input type="text" :name="`new_parents[${i}][phone]`" x-model="row.phone" placeholder="No. HP (opsional)" :disabled="!isStudent" class="{{ $fieldClass }}">
                <button type="button" @click="rows.splice(i, 1)" class="text-xs font-semibold text-rose-600 hover:underline justify-self-start sm:justify-self-end self-center cursor-pointer">Hapus baris ini</button>
            </div>
        </template>
        @if ($errors->has('new_parents.*'))
            <ul class="text-xs text-rose-600 space-y-0.5">
                @foreach (collect($errors->get('new_parents.*'))->flatten()->unique() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
