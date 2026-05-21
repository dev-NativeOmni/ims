<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Audit Log
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700">
                                Pencarian
                            </label>
                            <input
                                id="search"
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Cari user, model, ID, atau URL"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            >
                        </div>

                        <div>
                            <label for="event" class="block text-sm font-medium text-gray-700">
                                Event
                            </label>
                            <select
                                id="event"
                                name="event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            >
                                <option value="">Semua Event</option>
                                <option value="created" @selected(request('event') === 'created')>Created</option>
                                <option value="updated" @selected(request('event') === 'updated')>Updated</option>
                                <option value="deleted" @selected(request('event') === 'deleted')>Deleted</option>
                                <option value="restored" @selected(request('event') === 'restored')>Restored</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                            >
                                Filter
                            </button>

                            <a
                                href="{{ route('audit-logs.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                            >
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Event</th>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">URL</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($auditLogs as $auditLog)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $auditLog->created_at?->format('d M Y H:i') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $auditLog->user?->name ?? 'System' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $auditLog->user?->email ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                            {{ $auditLog->event_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ class_basename($auditLog->auditable_type) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            ID: {{ $auditLog->auditable_id }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 max-w-xs truncate">
                                        {{ $auditLog->url ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a
                                            href="{{ route('audit-logs.show', $auditLog) }}"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium"
                                        >
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada audit log.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $auditLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>