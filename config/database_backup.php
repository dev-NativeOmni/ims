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

    /*
    |--------------------------------------------------------------------------
    | mysql Path (restore)
    |--------------------------------------------------------------------------
    */

    'mysql_path' => env('BACKUP_MYSQL_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | File Unggahan
    |--------------------------------------------------------------------------
    |
    | Folder file unggahan (tanda tangan, foto, lampiran) yang ikut dibackup
    | sebagai .zip bila backup dijalankan dengan --with-files.
    |
    */

    'files_path' => storage_path('app/public'),

    /*
    |--------------------------------------------------------------------------
    | Google Drive Retention
    |--------------------------------------------------------------------------
    |
    | Backup di Google Drive yang lebih tua dari jumlah hari ini dihapus.
    |
    */

    'drive_retention_days' => (int) env('BACKUP_DRIVE_RETENTION_DAYS', 60),

];
