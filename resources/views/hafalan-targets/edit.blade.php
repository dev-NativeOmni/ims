<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Target Hafalan
            </h2>
            <p class="text-sm text-gray-500">
                Perbarui target hafalan santri.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div class="font-semibold">Ada input yang belum benar:</div>
                        <ul class="mt-2 list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('hafalan-targets.update', $target) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Santri</label>
                        <select name="student_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Pilih santri</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}"
                                        @selected((string) old('student_id', $target->student_id) === (string) $student->id)>
                                    {{ $student->name }}
                                    — {{ $student->classRoom?->name ?? '-' }}
                                    — Guru: {{ $student->teacher?->user?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Surah</label>
                        <select name="surah_id" required data-surah-select class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Pilih surah</option>
                            @foreach ($surahs as $surah)
                                <option value="{{ $surah->id }}"
                                        data-total-ayah="{{ $surah->total_ayah }}"
                                        @selected((string) old('surah_id', $target->surah_id) === (string) $surah->id)>
                                    {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                </option>
                            @endforeach
                        </select>
                        <p data-total-ayah-label class="mt-1 text-xs text-gray-500">
                            Pilih surah untuk melihat batas ayat.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ayat Mulai</label>
                            <input type="number" name="ayah_start" value="{{ old('ayah_start', $target->ayah_start) }}"
                                   min="1" required data-ayah-start
                                   class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ayat Akhir</label>
                            <input type="number" name="ayah_end" value="{{ old('ayah_end', $target->ayah_end) }}"
                                   min="1" required data-ayah-end
                                   class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Target</label>
                            <input type="date" name="target_date"
                                   value="{{ old('target_date', $target->target_date?->format('Y-m-d')) }}"
                                   required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option value="active" @selected(old('status', $target->status) === 'active')>Aktif</option>
                                <option value="completed" @selected(old('status', $target->status) === 'completed')>Selesai</option>
                                <option value="missed" @selected(old('status', $target->status) === 'missed')>Terlewat</option>
                                <option value="cancelled" @selected(old('status', $target->status) === 'cancelled')>Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="notes" rows="4"
                                  class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $target->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('hafalan-targets.index') }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>

                        <button type="submit"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const surahSelect = document.querySelector('[data-surah-select]');
            const ayahStart = document.querySelector('[data-ayah-start]');
            const ayahEnd = document.querySelector('[data-ayah-end]');
            const totalLabel = document.querySelector('[data-total-ayah-label]');

            function syncAyahLimit() {
                const selectedOption = surahSelect.options[surahSelect.selectedIndex];
                const totalAyah = selectedOption ? selectedOption.dataset.totalAyah : '';

                if (!totalAyah) {
                    totalLabel.textContent = 'Pilih surah untuk melihat batas ayat.';
                    ayahStart.removeAttribute('max');
                    ayahEnd.removeAttribute('max');
                    return;
                }

                ayahStart.setAttribute('max', totalAyah);
                ayahEnd.setAttribute('max', totalAyah);
                totalLabel.textContent = 'Maksimal ' + totalAyah + ' ayat untuk surah ini.';
            }

            surahSelect.addEventListener('change', syncAyahLimit);
            syncAyahLimit();
        });
    </script>
</x-app-layout>