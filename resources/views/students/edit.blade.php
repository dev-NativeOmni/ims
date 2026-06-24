@php
    $selectedParentIds = old('parent_ids', $student->parents->pluck('id')->toArray());

    $selectedParentRelations = old(
        'parent_relations',
        $student->parents
            ->mapWithKeys(fn ($parent) => [$parent->id => $parent->pivot->relation])
            ->toArray()
    );
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Santri
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('students.update', $student) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nama Santri
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $student->name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="student_number" class="block text-sm font-medium text-gray-700">
                                Nomor Santri / NIS
                            </label>
                            <input
                                id="student_number"
                                name="student_number"
                                type="text"
                                value="{{ old('student_number', $student->student_number) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                            @error('student_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="class_room_id" class="block text-sm font-medium text-gray-700">
                                Kelas
                            </label>
                            <select
                                id="class_room_id"
                                name="class_room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Kelas</option>
                                @foreach ($classRooms as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) old('class_room_id', $student->class_room_id) === (string) $classRoom->id)>
                                        {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_room_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700">
                                Guru Pembimbing
                            </label>
                            <select
                                id="teacher_id"
                                name="teacher_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $student->teacher_id) === (string) $teacher->id)>
                                        {{ $teacher->user?->name }}{{ $teacher->employee_number ? ' - ' . $teacher->employee_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">
                                Gender
                            </label>
                            <select
                                id="gender"
                                name="gender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Pilih Gender</option>
                                <option value="male" @selected(old('gender', $student->gender) === 'male')>Laki-laki</option>
                                <option value="female" @selected(old('gender', $student->gender) === 'female')>Perempuan</option>
                            </select>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-gray-700">
                                Tanggal Lahir
                            </label>
                            <input
                                id="birth_date"
                                name="birth_date"
                                type="date"
                                value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                            @error('birth_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select
                                id="status"
                                name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="active" @selected(old('status', $student->status) === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status', $student->status) === 'inactive')>Nonaktif</option>
                                <option value="graduated" @selected(old('status', $student->status) === 'graduated')>Lulus</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">
                                Akun Login Santri
                            </label>
                            <select
                                id="user_id"
                                name="user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Tidak dihubungkan dulu</option>
                                @foreach ($studentUsers as $studentUser)
                                    <option value="{{ $studentUser->id }}" @selected((string) old('user_id', $student->user_id) === (string) $studentUser->id)>
                                        {{ $studentUser->name }} - {{ $studentUser->username }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t pt-5">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            Relasi Orangtua/Wali
                        </h3>

                        <div class="space-y-3">
                            @forelse ($parents as $parent)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center border rounded-md p-3">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            name="parent_ids[]"
                                            value="{{ $parent->id }}"
                                            @checked(in_array($parent->id, $selectedParentIds))
                                            class="rounded border-gray-300"
                                        >
                                        <span>
                                            {{ $parent->user?->name }}
                                            <span class="text-sm text-gray-500">
                                                {{ $parent->phone ? ' - ' . $parent->phone : '' }}
                                            </span>
                                        </span>
                                    </label>

                                    <input
                                        type="text"
                                        name="parent_relations[{{ $parent->id }}]"
                                        value="{{ $selectedParentRelations[$parent->id] ?? '' }}"
                                        placeholder="Relasi, contoh: ayah / ibu / wali"
                                        class="rounded-md border-gray-300 shadow-sm"
                                    >
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">
                                    Belum ada data orangtua/wali.
                                </p>
                            @endforelse
                        </div>

                        @error('parent_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>