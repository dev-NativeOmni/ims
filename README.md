# TAD Management System (Tahfizh, Adab, Disiplin)
### SMA Islam Al Azhar 7 Solo Baru

![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Tests Passed](https://img.shields.io/badge/Tests-257%20Passed-10B981?style=for-the-badge&logo=checkmarx&logoColor=white)

**TAD Management System** adalah platform tata kelola terintegrasi (*Integrated Management System*) yang dirancang khusus untuk memonitor, mengevaluasi, dan melaporkan rekam jejak **Tahfizh (Hafalan Al-Qur'an)**, **Adab (Karakter Islami)**, dan **Disiplin (Ketertiban/Tanse)** santri secara transparan dan real-time di lingkungan SMA Islam Al Azhar 7 Solo Baru.

---

## 🌟 Fitur Utama & Modul Sistem

### 1. 📿 Tahfizh & Muraja'ah Tracker
* **Dua Jalur Kurikulum:**
  * **Program Reguler (Kelas XI & XII):** Monitoring target setoran baris, ayat, halaman, dan rekap kelulusan Juz 1 s/d 30.
  * **Program Metode Ummi (Kelas X):** Bimbingan jilid 1–6, Al-Qur'an, Ghorib/Tajwid, dan penilaian Munaqasyah.
* **Peta Perjalanan Milestone (Term 1 s/d 4):** Visualisasi rekam jejak capaian hafalan murid antar-semester dan jenjang kelas.
* **Spreadsheet Input Cepat:** Mode input massal fleksibel untuk ustadz/ustadzah menyimak santri dalam satu sesi halaqah.
* **Tampilan Prioritas:** Daftar surah terakhir yang dihafal beserta keterangan tanggal setoran langsung tampil di bagian paling atas halaman Murid dan Orang Tua.

### 2. 🌟 Pembiasaan Adab & Karakter
* **Evaluasi Harian:** Kuisioner pembiasaan ibadah (shalat fardhu berjamaah, dhuha, tahajjud, adab pergaulan, dan tilawah).
* **Penilaian Pendamping Adab:** Perhitungan skor adab berkala, konversi grade (A/B/C), dan catatan bimbingan akhlak.

### 3. 🛡️ Tanse (Ketertiban & Kedisiplinan Santri)
* **Pencatatan Poin Pelanggaran & Prestasi:** Sistem tabulasi transparan untuk poin pembinaan dan apresiasi prestasi santri.
* **Monitoring Wali Murid:** Orang tua dapat memantau kedisiplinan ananda secara langsung dari portal wali.

### 4. 📊 Pelaporan Terpadu & Rapor
* **Cetak Rapor Otomatis:** Cetak Rapor Tahfizh dan Karakter dengan layout browser-native beresolusi tinggi (bebas dependensi server, font kaligrafi Arab tampil sempurna).
* **Export Excel (.xlsx) Streaming Native:** Ekspor data rekap murid, guru, nilai, dan capaian dengan `SimpleXlsxWriter` tanpa membebani RAM server.
* **Visualisasi Grafik Lokal:** Dashboard interaktif dengan Chart.js offline-ready (tanpa dependensi CDN eksternal).

### 5. 🔄 Sistem Multi-Role Dinamis & Role Switcher
Memiliki 9 tingkatan hak akses khusus yang aman dan terisolasi:
1. **Super Admin:** Kontrol penuh sistem, manajemen pengguna, migrasi, dan konfigurasi master.
2. **Admin:** Pengelolaan data akademik, murid, guru, dan kelas.
3. **Guru / Pembimbing Tahfizh:** Penginputan hafalan, muraja'ah, spreadsheet, dan target santri bimbingan.
4. **Pendamping Adab:** Evaluasi dan penilaian karakter harian kelas binaan.
5. **Tanse:** Pengelolaan ketertiban dan catatan kedisiplinan/prestasi santri.
6. **Koordinator Tahfizh:** Monitoring makro capaian tahfizh dan validasi ujian juz.
7. **Kepala Sekolah:** Dashboard eksekutif performa sekolah, grafik periodik, dan evaluasi guru.
8. **Orang Tua / Wali:** Portal monitoring 360° capaian hafalan, adab, dan kedisiplinan ananda.
9. **Murid:** Dashboard mandiri untuk melihat progres hafalan, target aktif, dan riwayat setoran.
* **Role Switcher Instan:** Pengguna yang memegang peran ganda (misal Guru yang merangkap Pendamping Adab atau Tanse) dapat berganti peran langsung dari menu profil tanpa perlu logout.

### 6. 📖 Mushaf Digital & Audio Al-Qur'an
* Dilengkapi mushaf Al-Qur'an digital interaktif serta pemutar audio murottal per surah untuk kemudahan muraja'ah mandiri santri.

---

## 🛠️ Tech Stack

* **Backend Framework:** [Laravel 12](https://laravel.com) (PHP 8.2+)
* **Database:** MySQL / MariaDB (Production), SQLite In-Memory (Testing)
* **Frontend:** Blade Templates, [Tailwind CSS v4](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
* **Build Tool:** [Vite 6](https://vitejs.dev)
* **Visualisasi Data:** [Chart.js](https://www.chartjs.org) + [chartjs-plugin-datalabels](https://chartjs-plugin-datalabels.netlify.app)
* **Testing:** PHPUnit 11 (257 Test Cases, 1.154 Assertions)

---

## 🚀 Panduan Instalasi Lokal

### Prasyarat
* PHP >= 8.2 (ekstensi: `pdo`, `mbstring`, `zip`, `gd`, `intl`, `xml`)
* Composer >= 2.x
* Node.js >= 20.x & NPM
* MySQL / MariaDB

### Langkah Instalasi

1. **Clone repositori:**
   ```bash
   git clone https://github.com/dev-NativeOmni/ims.git
   cd ims
   ```

2. **Install dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan konfigurasi database Anda di file `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tad_management
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan Migrasi & Seeder:**
   ```bash
   php artisan migrate --seed
   ```

5. **Kompilasi Asset:**
   ```bash
   npm run build
   # atau untuk mode pengembangan:
   npm run dev
   ```

6. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser pada `http://127.0.0.1:8000`.

---

## 🧪 Pengujian Otomatis (Automated Testing)

Aplikasi memiliki rangkaian automated feature dan unit test yang mencakup autentikasi, isolasi data antar-role, perhitungan capaian kurikulum, dan fungsi ekspor.

Jalankan test suite dengan perintah:
```bash
php artisan test
```

Pipeline CI/CD otomatis berjalan di setiap push/pull request melalui GitHub Actions ([`.github/workflows/laravel-ci.yml`](.github/workflows/laravel-ci.yml)).

---

## 📁 Dokumentasi Tambahan

Panduan operasional dan referensi teknis dapat ditemukan di direktori [`docs/`](docs/):
* [`docs/PANDUAN_MIGRASI_DOMAIN_TAD.md`](docs/PANDUAN_MIGRASI_DOMAIN_TAD.md) — Panduan migrasi domain dan deployment VPS produksi.
* [`docs/SKRIP_VIDEO_TUTORIAL_ORANGTUA.md`](docs/SKRIP_VIDEO_TUTORIAL_ORANGTUA.md) — Panduan skrip video edukasi wali murid.

---

## 📄 Hak Cipta & Lisensi

Hak Cipta &copy; 2026 **SMA Islam Al Azhar 7 Solo Baru**. Seluruh hak cipta dilindungi undang-undang.
Dikembangkan untuk mendukung kemajuan pendidikan Islam terpadu dan pembinaan generasi Qur'ani.
