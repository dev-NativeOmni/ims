<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-cloud-arrow-up class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span>Backup &amp; Restore</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Backup database &amp; file unggahan, salinan otomatis ke Google Drive, dan pemulihan data.
            </p>
        </div>
    </x-slot>

    @php
        $formatBytes = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 2).' MB' : number_format($bytes / 1024, 1).' KB';
        $typeBadge = fn (?string $type) => $type === 'files'
            ? ['File unggahan', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300']
            : ['Database', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'];
    @endphp

    <div class="py-8" x-data="{ restore: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-rose-200 bg-rose-50 text-rose-800'] as $key => $classes)
                @if (session($key))
                    <div class="rounded-xl border px-4 py-3 text-sm font-semibold shadow-sm {{ $classes }}">{{ session($key) }}</div>
                @endif
            @endforeach
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Backup Terakhir</p>
                    @if ($lastStatus['at'])
                        <p class="mt-1 text-lg font-extrabold {{ $lastStatus['ok'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }}">
                            {{ $lastStatus['ok'] ? 'Berhasil' : 'Gagal' }}
                        </p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($lastStatus['at'])->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                        @if ($lastStatus['message'])
                            <p class="mt-1 text-[11px] text-rose-600">{{ \Illuminate\Support\Str::limit($lastStatus['message'], 140) }}</p>
                        @endif
                    @else
                        <p class="mt-1 text-lg font-extrabold text-gray-400">Belum ada</p>
                    @endif
                </div>
                <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Jadwal Otomatis</p>
                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">Setiap hari 03:00</p>
                    <p class="text-xs text-gray-500">Database setiap hari · + file unggahan setiap Ahad</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Google Drive</p>
                    @if ($driveConnected)
                        <p class="mt-1 text-lg font-extrabold text-emerald-600 dark:text-emerald-400">Terhubung</p>
                        <p class="text-xs text-gray-500 truncate">{{ $driveEmail }}</p>
                    @else
                        <p class="mt-1 text-lg font-extrabold text-amber-600">{{ $driveConfigured ? 'Belum terhubung' : 'Belum diatur' }}</p>
                        <p class="text-xs text-gray-500">Backup hanya tersimpan di server</p>
                    @endif
                </div>
                <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Masa Simpan</p>
                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">Server {{ $retentionDays }} hari</p>
                    <p class="text-xs text-gray-500">Google Drive {{ $driveRetentionDays }} hari</p>
                </div>
            </div>

            {{-- Buat backup --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Buat Backup Sekarang</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                        Database disimpan sebagai .sql.gz; file unggahan (tanda tangan, foto, lampiran) sebagai .zip.
                        {{ $driveConnected ? 'Salinan otomatis dikirim ke Google Drive.' : '' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('database-backups.store') }}" class="flex flex-wrap gap-2" onsubmit="this.querySelectorAll('button').forEach(b => b.disabled = true)">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm cursor-pointer disabled:opacity-50">Backup Database</button>
                    <button type="submit" name="with_files" value="1" class="px-4 py-2 rounded-xl bg-white dark:bg-zinc-800 border border-indigo-200 dark:border-zinc-700 text-indigo-700 dark:text-indigo-300 text-sm font-bold shadow-sm cursor-pointer disabled:opacity-50">Database + File Unggahan</button>
                </form>
            </div>

            {{-- Google Drive --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Salinan Google Drive</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Backup disalin ke folder "{{ config('services.google_drive.folder_name') }}" supaya aman walau server rusak.</p>
                    </div>
                    @if ($canRestore && $driveConfigured)
                        @if ($driveConnected)
                            <form method="POST" action="{{ route('database-backups.drive.disconnect') }}" onsubmit="return confirm('Putuskan Google Drive? Backup berikutnya hanya tersimpan di server.')">
                                @csrf
                                <button class="px-4 py-2 rounded-xl border border-rose-200 text-rose-700 text-sm font-bold cursor-pointer">Putuskan Google Drive</button>
                            </form>
                        @else
                            <a href="{{ route('database-backups.drive.connect') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-sm text-center">Hubungkan Google Drive</a>
                        @endif
                    @endif
                </div>

                @if (! $driveConfigured)
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 text-xs text-amber-900 dark:text-amber-200 space-y-1">
                        <p class="font-bold">Google Drive belum diatur di server.</p>
                        <p>Isi <code>GOOGLE_DRIVE_CLIENT_ID</code> dan <code>GOOGLE_DRIVE_CLIENT_SECRET</code> di file .env (OAuth Client "Web application" di Google Cloud Console, Google Drive API diaktifkan), dengan Authorized redirect URI:</p>
                        <p class="font-mono break-all">{{ $driveRedirectUri }}</p>
                        <p>Lalu jalankan <code>php artisan config:cache</code> dan klik "Hubungkan Google Drive".</p>
                    </div>
                @elseif ($driveError)
                    <p class="rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">{{ $driveError }}</p>
                @elseif ($driveConnected)
                    <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-zinc-800">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">File di Google Drive</th>
                                    <th class="px-4 py-2 text-left">Jenis</th>
                                    <th class="px-4 py-2 text-left">Ukuran</th>
                                    <th class="px-4 py-2 text-left">Dibuat</th>
                                    <th class="px-4 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                @forelse ($driveFiles as $file)
                                    @php [$typeLabel, $typeClass] = $typeBadge(\App\Services\Backup\BackupManager::typeOf($file['name'])); @endphp
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-zinc-200 break-all">{{ $file['name'] }}</td>
                                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $typeClass }}">{{ $typeLabel }}</span></td>
                                        <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $formatBytes($file['size']) }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($file['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 text-right">
                                            @if ($canRestore)
                                                <button type="button" class="px-3 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold cursor-pointer"
                                                        x-on:click="restore = { source: 'drive', driveId: @js($file['id']), name: @js($file['name']) }">Pulihkan</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-xs text-gray-400">Belum ada backup di Google Drive.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Backup di server --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="p-5 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Backup di Server ({{ count($backups) }})</h3>
                    <p class="text-xs text-gray-500 break-all">{{ $backupPath }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left">Nama File</th>
                                <th class="px-4 py-2 text-left">Jenis</th>
                                <th class="px-4 py-2 text-left">Ukuran</th>
                                <th class="px-4 py-2 text-left">Dibuat</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse ($backups as $backup)
                                @php [$typeLabel, $typeClass] = $typeBadge($backup['type']); @endphp
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-zinc-200 break-all">
                                        {{ $backup['filename'] }}
                                        @if (str_contains($backup['filename'], 'sebelum-restore'))
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-bold">pengaman</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $typeClass }}">{{ $typeLabel }}</span></td>
                                    <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $formatBytes($backup['size_bytes']) }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ date('d/m/Y H:i', $backup['modified']) }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <a href="{{ route('database-backups.download', $backup['filename']) }}" class="px-3 py-1 rounded-lg border border-gray-200 dark:border-zinc-700 text-xs font-bold text-gray-700 dark:text-zinc-300">Unduh</a>
                                            @if ($canRestore)
                                                @if ($driveConnected)
                                                    <form method="POST" action="{{ route('database-backups.drive.send', $backup['filename']) }}">
                                                        @csrf
                                                        <button class="px-3 py-1 rounded-lg border border-emerald-200 text-emerald-700 text-xs font-bold cursor-pointer">Ke Drive</button>
                                                    </form>
                                                @endif
                                                <button type="button" class="px-3 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold cursor-pointer"
                                                        x-on:click="restore = { source: 'local', filename: @js($backup['filename']), name: @js($backup['filename']) }">Pulihkan</button>
                                                <form method="POST" action="{{ route('database-backups.destroy', $backup['filename']) }}" onsubmit="return confirm('Hapus file backup ini dari server?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="px-3 py-1 rounded-lg text-xs font-bold text-gray-400 hover:text-rose-600 cursor-pointer">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-xs text-gray-400">Belum ada backup di server.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pulihkan dari file yang diunggah --}}
            @if ($canRestore)
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Pulihkan dari File Komputer</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Unggah file hasil backup (.sql, .sql.gz, atau .zip). Batas unggah server: {{ $maxUpload }}.</p>
                    </div>
                    <button type="button" class="px-4 py-2 rounded-xl border border-rose-200 text-rose-700 text-sm font-bold cursor-pointer"
                            x-on:click="restore = { source: 'upload', name: '' }">Pilih File &amp; Pulihkan</button>
                </div>
            @endif
        </div>

        {{-- Konfirmasi restore --}}
        @if ($canRestore)
            <div x-show="restore" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" x-on:keydown.escape.window="restore = null">
                <form method="POST" action="{{ route('database-backups.restore') }}" enctype="multipart/form-data"
                      class="w-full max-w-lg rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-2xl space-y-4" x-on:click.outside="restore = null"
                      onsubmit="this.querySelector('[type=submit]').disabled = true; this.querySelector('[type=submit]').innerText = 'Memulihkan… jangan tutup halaman'">
                    @csrf
                    <input type="hidden" name="source" x-bind:value="restore?.source">
                    <input type="hidden" name="filename" x-bind:value="restore?.filename">
                    <input type="hidden" name="drive_id" x-bind:value="restore?.driveId">
                    <input type="hidden" name="drive_name" x-bind:value="restore?.name">

                    <div>
                        <h3 class="text-lg font-bold text-rose-700">Pulihkan Data</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-zinc-300" x-show="restore?.name">
                            Dari: <span class="font-mono text-xs break-all" x-text="restore?.name"></span>
                        </p>
                    </div>

                    <div class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 p-3 text-xs text-rose-900 dark:text-rose-200 space-y-1">
                        <p class="font-bold">Perhatian:</p>
                        <p>• Backup database mengganti <strong>seluruh data</strong> dengan isi backup. Data yang diinput setelah backup itu dibuat akan hilang.</p>
                        <p>• Backup file (.zip) menimpa file unggahan dengan nama yang sama.</p>
                        <p>• Sebelum restore database, sistem otomatis membuat backup pengaman ("sebelum-restore") untuk membatalkan.</p>
                    </div>

                    <div x-show="restore?.source === 'upload'">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">File backup</label>
                        <input type="file" name="backup_file" accept=".sql,.gz,.zip" class="block w-full text-sm" x-bind:required="restore?.source === 'upload'">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">Ketik <span class="font-mono text-rose-700">{{ $confirmation }}</span> untuk melanjutkan</label>
                        <input type="text" name="confirmation" autocomplete="off" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">Password Anda</label>
                        <input type="password" name="password" autocomplete="current-password" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-gray-200 dark:border-zinc-700 text-sm font-bold text-gray-700 dark:text-zinc-300 cursor-pointer" x-on:click="restore = null">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm cursor-pointer disabled:opacity-60">Pulihkan Sekarang</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
