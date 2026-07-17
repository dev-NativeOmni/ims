# Panduan Deployment di Proxmox LXC Container (CT)

Panduan ini menjelaskan cara melakukan deployment **HafizPlus 2.0** pada **LXC Container (CT)** di server Proxmox VE sekolah Anda.

---

## Langkah 1: Pembuatan LXC Container di Proxmox VE

1. Masuk ke dashboard Proxmox VE sekolah Anda.
2. Klik tombol **Create CT** di pojok kanan atas.
3. Konfigurasikan detail container berikut:
   *   **Node:** Pilih node Proxmox Anda.
   *   **Hostname:** `hafizplus-server`
   *   **Password:** Masukkan password root yang kuat.
   *   **Template:** Unduh dan gunakan template **Ubuntu 22.04 LTS** atau **Ubuntu 24.04 LTS** standard (bukan minimal).
   *   **Disks:** Alokasikan minimal **20 GiB** (Storage type: local-lvm atau storage cluster lainnya).
   *   **CPU:** Alokasikan minimal **2 Cores**.
   *   **Memory:** Alokasikan minimal **2048 MiB (2 GB)** RAM + **512 MiB** Swap. *(Rekomendasi: 4096 MiB RAM jika server sekolah memiliki resource longgar)*.
   *   **Network:**
       *   **Bridge:** `vmbr0` (atau network bridge default Proxmox).
       *   **IPv4:** Pilih `Static` jika ingin IP tetap di jaringan sekolah (misal: `192.168.1.50/24`) dengan Gateway (misal: `192.168.1.1`), atau pilih `DHCP` jika IP akan otomatis diberikan oleh router sekolah.
4. Selesaikan pembuatan container dan jalankan (klik **Start**).

---

## Langkah 2: Setup Server Otomatis via Skrip Bash

Setelah container aktif, masuk ke **Console** container tersebut di Proxmox (atau via SSH menggunakan IP container) sebagai user `root`. 

Kami telah menyiapkan skrip bash otomatis untuk melakukan instalasi Nginx, PHP 8.2, MariaDB/MySQL, Node.js, Composer, Supervisor, dan Cron.

Jalankan perintah berikut di dalam terminal Proxmox CT Anda untuk mengunduh dan menjalankan skrip installer:

```bash
# Update sistem dan instal curl
apt update && apt install -y curl

# Jalankan skrip setup otomatis (Buat file terlebih dahulu)
nano setup-server.sh
```
*Salin seluruh kode dari file [setup-server.sh](file:///c:/xampp/htdocs/hafizplus-2.0/docs/setup-server.sh) (tertera di bawah) ke dalam editor nano tersebut, simpan (Ctrl+O, Enter) dan keluar (Ctrl+X).*

Kemudian jalankan:
```bash
chmod +x setup-server.sh
./setup-server.sh
```

---

## Langkah 3: Menanamkan Codebase Aplikasi ke Server

Setelah skrip setup server selesai dijalankan, web server dan database Anda telah siap. Selanjutnya adalah memindahkan kode aplikasi dari komputer Windows lokal Anda ke Proxmox CT.

### Opsi A: Menggunakan Git (Sangat Direkomendasikan)
1. Push codebase lokal Anda ke repository Git privat (seperti GitHub atau GitLab).
2. Di dalam konsol Proxmox CT, jalankan perintah untuk meng-clone repository Anda langsung ke direktori web:
   ```bash
   cd /var/www
   rm -rf html
   git clone <URL_REPOSITORY_ANDA> hafizplus
   cd hafizplus
   ```

### Opsi B: Transfer File Langsung (SCP/SFTP)
Jika Anda menggunakan aplikasi SFTP seperti **FileZilla** atau **WinSCP**:
1. Hubungkan FileZilla ke IP Proxmox CT Anda menggunakan protokol SFTP dengan port `22`, username `root`, dan password yang Anda buat di Langkah 1.
2. Upload folder proyek `hafizplus-2.0` dari Windows ke direktori `/var/www/` di server.
3. Ubah nama folder di server menjadi `/var/www/hafizplus`.

---

## Langkah 4: Inisialisasi Aplikasi Laravel di Server Proxmox CT

Setelah file proyek berada di direktori `/var/www/hafizplus` Proxmox CT, jalankan perintah berikut sebagai user `root` di terminal server untuk memproses instalasi dependencies dan database:

```bash
cd /var/www/hafizplus

# Berikan kepemilikan folder ke user web server (www-data)
chown -R www-data:www-data /var/www/hafizplus
chmod -R 775 /var/www/hafizplus/storage
chmod -R 775 /var/www/hafizplus/bootstrap/cache

# Salin file environment dan konfigurasikan
cp .env.example .env
nano .env
```

Sesuaikan baris berikut di dalam editor `.env` server:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<IP_ADDRESS_PROXMOX_CT_ANDA>

DB_DATABASE=hafizplus
DB_USERNAME=hafizplus_user
DB_PASSWORD=PasswordKuatYangAndaMasukkanDiSkripSetup
```

Setelah `.env` disimpan, jalankan proses build Laravel:
```bash
# Jalankan Composer Install untuk production
composer install --no-dev --optimize-autoloader

# Generate security key
php artisan key:generate

# Jalankan migrasi database beserta data master awal
php artisan migrate --force
php artisan db:seed --class=CoreDataSeeder --force
php artisan db:seed --class=QuranDataSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force

# Install dependencies frontend dan kompilasi aset
npm install
npm run build

# Buat symbolic link untuk storage upload file
php artisan storage:link

# Optimasi performa cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Aplikasi **HafizPlus 2.0** sekarang sudah berjalan dan dapat diakses di browser melalui IP Address container Proxmox Anda di lingkungan sekolah!
