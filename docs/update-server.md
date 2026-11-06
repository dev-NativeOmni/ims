# Memperbarui Aplikasi di Server Production

Server production TAD: Proxmox CT 100 (`ims-server`) dengan aaPanel.

| | |
|---|---|
| Folder aplikasi | `/www/wwwroot/ims.smaia7.sch.id` |
| Pemilik file | user & grup `www` |
| Database | MySQL/MariaDB `ims` (kredensial di `.env`) |
| PHP | aaPanel `/www/server/php/83` & `84` (CLI: 8.3) |

Semua perintah di bawah dijalankan sebagai **root** di console CT. Perintah aplikasi dijalankan atas nama `www`
(`runuser -u www --`) supaya file yang dibuat tetap milik `www`.

## Sebelum update

1. Pastikan tes GitHub (Laravel CI) untuk commit terbaru di `main` berwarna hijau.
2. Lakukan di luar jam input guru.
3. Buat cadangan, pilih salah satu:
   - Proxmox: **CT 100 → Snapshots → Take Snapshot** (hapus snapshot setelah update terbukti normal), atau
   - backup database dengan kredensial dari `.env`:

     ```bash
     cd /www/wwwroot/ims.smaia7.sch.id
     DBU=$(grep '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')
     DBP=$(grep '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')
     MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces ims > /root/backup-ims-$(date +%F-%H%M).sql
     ls -lh /root/backup-ims-*.sql
     ```

## Update

```bash
cd /www/wwwroot/ims.smaia7.sch.id
runuser -u www -- git status --short        # harus kosong; bila tidak, berhenti dan periksa
runuser -u www -- git log --oneline -1      # catat hash ini untuk rollback

runuser -u www -- php artisan down --retry=60
runuser -u www -- git pull origin main
runuser -u www -- php artisan migrate --force
runuser -u www -- php artisan optimize:clear
runuser -u www -- php artisan optimize
/etc/init.d/php-fpm-83 reload; /etc/init.d/php-fpm-84 reload
runuser -u www -- php artisan up
```

Bila commit yang ditarik mengubah `composer.json`/`composer.lock`, jalankan juga sebelum `migrate`:
`runuser -u www -- env HOME=/tmp composer install --no-dev --optimize-autoloader`.
Aset tampilan (`public/build`) sudah ikut di repo, jadi `npm` tidak perlu dijalankan di server.

## Sesudah update

```bash
grep -c "production.ERROR" storage/logs/laravel.log     # catat, buka beberapa halaman, ulangi: angka tidak boleh bertambah
grep "production.ERROR" storage/logs/laravel.log | tail -3 | cut -c1-400
```

Untuk `php artisan tinker` sebagai `www`, tambahkan folder konfigurasi sementara:
`runuser -u www -- env XDG_CONFIG_HOME=/tmp/psysh-www php artisan tinker`.

## Rollback

```bash
cd /www/wwwroot/ims.smaia7.sch.id
runuser -u www -- git reset --hard <hash-yang-dicatat>
runuser -u www -- php artisan optimize:clear
runuser -u www -- php artisan optimize
/etc/init.d/php-fpm-83 reload; /etc/init.d/php-fpm-84 reload
```

Bila update menjalankan migration yang mengubah struktur tabel, pulihkan juga database dari backup
(atau rollback snapshot Proxmox).
