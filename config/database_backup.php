<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Backup Path
    |--------------------------------------------------------------------------
    |
    | Backup disimpan di storage lokal aplikasi. Folder ini tidak boleh dibuat
    | public dan tidak boleh ikut commit GitHub.
    |
    */

    'path' => storage_path('app/backups/database'),

    /*
    |--------------------------------------------------------------------------
    | mysqldump Path
    |--------------------------------------------------------------------------
    |
    | Untuk XAMPP Windows biasanya:
    | C:\xampp\mysql\bin\mysqldump.exe
    |
    */

    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Backup lama akan dihapus otomatis setelah melewati jumlah hari ini.
    |
    */

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Batas waktu proses backup dalam detik.
    |
    */

    'timeout' => (int) env('BACKUP_TIMEOUT', 300),

];
