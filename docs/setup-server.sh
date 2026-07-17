#!/bin/bash

# ==============================================================================
# SKRIP SETUP OTOMATIS SERVER LAUNCHER - HAFIZPLUS 2.0 (PROXMOX LXC UBUNTU)
# ==============================================================================
# Skrip ini menginstal Nginx, PHP 8.2 + Ekstensi, MariaDB Server, Composer,
# Node.js 20, Supervisor, dan mendaftarkan Cron Job Scheduler Laravel.
# ==============================================================================

# Pastikan dijalankan sebagai root
if [ "$EUID" -ne 0 ]; then
  echo "Harap jalankan skrip ini menggunakan akses root (sudo)."
  exit 1
fi

echo "======================================================================"
echo "Memulai instalasi dependencies server untuk HafizPlus 2.0..."
echo "======================================================================"

# 1. Update & Upgrade OS
echo "--> Memperbarui paket sistem..."
apt update && apt upgrade -y
apt install -y software-properties-common curl git unzip nano supervisor cron ufw

# 2. Tambahkan Repositori PHP Ondrej
echo "--> Menambahkan repositori PHP Ondrej..."
add-apt-repository ppa:ondrej/php -y
apt update

# 3. Instal PHP 8.2 dan Ekstensi yang Dibutuhkan
echo "--> Menginstal PHP 8.2 dan modul-modulnya..."
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
            php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-sqlite3 \
            php8.2-intl php8.2-soap php8.2-opcache

# 4. Instal Web Server Nginx
echo "--> Menginstal Nginx..."
apt install -y nginx

# 5. Instal MariaDB Server (MySQL)
echo "--> Menginstal MariaDB Server..."
apt install -y mariadb-server

# Nyalakan dan aktifkan MariaDB
systemctl start mariadb
systemctl enable mariadb

# 6. Buat Database dan User HafizPlus
echo "--> Mengonfigurasi Database MariaDB..."
DB_PASS="HafizPlusPass2026!" # Ganti password ini dengan password yang aman
mariadb -e "CREATE DATABASE IF NOT EXISTS hafizplus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mariadb -e "CREATE USER IF NOT EXISTS 'hafizplus_user'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mariadb -e "GRANT ALL PRIVILEGES ON hafizplus.* TO 'hafizplus_user'@'localhost';"
mariadb -e "FLUSH PRIVILEGES;"

echo "--------------------------------------------------------"
echo "DATABASE BERHASIL DIKONFIGURASI:"
echo "Nama DB   : hafizplus"
echo "User DB   : hafizplus_user"
echo "Password  : ${DB_PASS}"
echo "--------------------------------------------------------"

# 7. Instal Composer (PHP Dependency Manager)
echo "--> Menginstal Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# 8. Instal Node.js 20 & NPM (NodeSource)
echo "--> Menginstal Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# 9. Konfigurasi Nginx Server Block untuk Laravel
echo "--> Membuat file konfigurasi Nginx..."
cat << 'EOF' > /etc/nginx/sites-available/hafizplus
server {
    listen 80;
    listen [::]:80;
    server_name _; # Merespons semua IP Address Proxmox CT Anda

    root /var/www/hafizplus/public;
    index index.php index.html index.htm;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Aktifkan situs baru dan matikan situs default
ln -sf /etc/nginx/sites-available/hafizplus /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Restart Nginx
systemctl restart nginx

# 10. Konfigurasi Supervisor untuk Laravel Queue
echo "--> Membuat konfigurasi Queue Worker via Supervisor..."
cat << 'EOF' > /etc/supervisor/conf.d/hafizplus-worker.conf
[program:hafizplus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hafizplus/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hafizplus/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Restart Supervisor
systemctl daemon-reload
systemctl restart supervisor

# 11. Mendaftarkan Cron Job untuk Laravel Scheduler
echo "--> Menambahkan Laravel Scheduler ke crontab system..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/hafizplus && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# 12. Konfigurasi Firewall Dasar (UFW)
echo "--> Mengonfigurasi UFW Firewall (Membuka SSH, HTTP)..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
# ufw --force enable # Hilangkan komentar jika ingin mengaktifkan firewall bawaan otomatis

echo "======================================================================"
echo "INSTALASI BERHASIL!"
echo "----------------------------------------------------------------------"
echo "Server Nginx, PHP 8.2, MariaDB, Composer, Node.js, dan Supervisor"
echo "telah terpasang dengan sukses pada Proxmox LXC Container Anda."
echo ""
echo "Silakan lanjutkan ke Langkah 3 pada Panduan (docs/proxmox-ct-setup.md)"
echo "untuk meng-upload kode proyek Anda ke /var/www/hafizplus"
echo "======================================================================"
