<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Audit Log
            </h2>

            <a
                href="{{ route('audit-logs.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
            >
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <div class="text-gray-500">Waktu</div>
                        <div class="font-semibold text-gray-900">
                            {{ $auditLog->created_at?->format('d M Y H:i:s') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">Event</div>
                        <div class="font-semibold text-gray-900">
                            {{ $auditLog->event_label }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">User</div>
                        <div class="font-semibold text-gray-900">
                            {{ $auditLog->user?->name ?? 'System' }}
                        </div>
                        <div class="text-gray-500">
                            {{ $auditLog->user?->email ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">Data</div>
                        <div class="font-semibold text-gray-900">
                            {{ class_basename($auditLog->auditable_type) }}
                        </div>
                        <div class="text-gray-500">
                            ID: {{ $auditLog->auditable_id }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">IP Address</div>
                        <div class="font-semibold text-gray-900">
                            {{ $auditLog->ip_address ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">URL</div>
                        <div class="font-semibold text-gray-900 break-all">
                            {{ $auditLog->url ?? '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-gray-500">User Agent</div>
                        <div class="font-semibold text-gray-900 break-all">
                            {{ $auditLog->user_agent ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            Old Values
                        </h3>

                        <pre class="bg-gray-900 text-gray-100 rounded-md p-4 overflow-x-auto text-xs">{{ $auditLog->old_values ? json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            New Values
                        </h3>

                        <pre class="bg-gray-900 text-gray-100 rounded-md p-4 overflow-x-auto text-xs">{{ $auditLog->new_values ? json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>