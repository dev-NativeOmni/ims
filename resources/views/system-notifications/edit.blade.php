<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Notifikasi Sistem
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Perbarui notifikasi yang sudah dibuat.
                </p>
            </div>

            <a href="{{ route('system-notifications.show', $systemNotification) }}"
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('system-notifications.update', $systemNotification) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Pengguna Penerima
                        </label>

                        <select name="user_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((int) old('user_id', $systemNotification->user_id) === $user->id)>
                                    {{ $user->name }} — {{ $user->username }} — {{ $user->role?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">
                                Role Target
                            </label>

                            <select name="target_role"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pengguna tertentu</option>
                                @foreach ($availableRoles as $value => $label)
                                    <option value="{{ $value }}" @selected(old('target_role', $systemNotification->target_role) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">
                                Tipe
                            </label>

                            <select name="type"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach ($availableTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $systemNotification->type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Judul
                        </label>

                        <input type="text"
                               name="title"
                               value="{{ old('title', $systemNotification->title) }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               required>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Pesan
                        </label>

                        <textarea name="message"
                                  rows="6"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                  required>{{ old('message', $systemNotification->message) }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Action URL
                        </label>

                        <input type="text"
                               name="action_url"
                               value="{{ old('action_url', $systemNotification->action_url) }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">
                                Published At
                            </label>

                            <input type="datetime-local"
                                   name="published_at"
                                   value="{{ old('published_at', $systemNotification->published_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">
                                Expires At
                            </label>

                            <input type="datetime-local"
                                   name="expires_at"
                                   value="{{ old('expires_at', $systemNotification->expires_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('system-notifications.show', $systemNotification) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>