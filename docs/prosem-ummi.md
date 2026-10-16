# Prosem Ummi & Alur Pembelajaran (Kelas 10)

Permintaan sekolah, Oktober 2026. Kode utama: `app/Services/UmmiProsemService.php`.

## Prosem (Program Semester)

- Tabel `ummi_prosem_rows`: satu baris = satu unit materi.
  - Isi baris: Jilid, halaman peraga, halaman buku, hafalan (ziyadah), dan drill (muraja'ah).
  - Kolom `weeks` berisi sebaran tatap muka (TM) per pekan, contohnya `{"7": {"4": 4}}` artinya pekan ke-4 Juli ada 4 TM.
- Jumlah TM satu baris = jumlah sebaran pekannya.
- Halaman: **Target → tab Prosem Ummi** (`/prosem-ummi`).
  - Semua staf bisa melihat.
  - Super admin, admin, dan koordinator tahfizh bisa mengubah.
- Impor dari Google Sheet, dengan susunan sama seperti tab "Prosem tahfidz sem 1":

  ```bash
  php artisan tad:prosem-import "https://docs.google.com/spreadsheets/d/<id>/edit" --tab="Prosem tahfidz sem 1" --tahun=2026/2027 --semester=1          # tampil
  php artisan tad:prosem-import "..." --tab="..." --tahun=2026/2027 --semester=1 --simpan                                                                   # simpan
  ```

  - Sheet harus dibagikan dengan akses "siapa saja yang memiliki link".
  - Nama surah dicocokkan dengan `App\Support\SurahNameMatcher` (contoh: "Al Ashr" menjadi Al-'Asr, "Quroisy" menjadi Quraish).
  - Baris yang tidak dikenali atau halaman yang terbalik (misalnya 9-1) muncul sebagai peringatan. Betulkan di halaman Prosem.

## Target per pertemuan

- `plan()` menyusun urutan TM semester: bulan → pekan 1-5 → urutan baris.
- Halaman peraga dan buku sebuah baris **dibagi rata** ke TM-nya. Sisa halaman diberikan ke TM awal. Contoh: hlm 1-8 dalam 4 TM menjadi 1-2, 3-4, 5-6, 7-8.
- Hafalan dan drill berlaku di semua TM baris itu.
- Rentang halaman terbalik tidak dibagi.
- Baris tanpa materi berarti TM pengulangan atau evaluasi.
- Pekan ke-n dalam bulan memakai pekan Senin-Minggu. Pekan 1 adalah pekan yang memuat tanggal 1.
- `meetingFor(kelas, tanggal)` menentukan TM hari itu: pekan Prosem tanggal itu, ditambah urutan hari pertemuan Ummi kelas itu di pekan tersebut (Senin-Kamis, kalender akademik).

## Target Ummi bulanan otomatis

- `monthTarget()` mengambil posisi TM terakhir sampai akhir bulan: Jilid, halaman buku, halaman peraga, dan surah hafalan terakhir.
- `syncTargets()` membuat atau memperbarui `hafalan_targets` dengan `auto_month = 'YYYY-MM'` dan catatan "Otomatis dari Prosem Ummi". Fungsi ini berlaku untuk murid Kelas 10 metode Ummi.
- Target yang dibuat atau diubah guru (`auto_month` kosong) **tidak ditimpa**. Bulan yang terkunci tidak disentuh.
- Prosem yang masih kosong tidak mengubah target apa pun.
- Sinkronisasi dijalankan saat Prosem disimpan atau diimpor, dan saat tab Target Ummi dibuka.

## Alur Pembelajaran (timer)

- Menu **Tahfizh → Alur Pembelajaran Ummi** (`/alur-pembelajaran`).
- Sesi bawaan: Pembukaan 5, Muraja'ah 10, Ziyadah 10, Klasikal peraga & baca simak 40, Penutup 5. Totalnya 70 menit.
- Durasi bisa diatur super admin, admin, dan koordinator tahfizh. Disimpan di setting `ummi_flow_minutes`.
- Timer berjalan di browser (`resources/js/components/ummiFlowTimer.js`, logika di `resources/js/ummi-flow.js`, tes di `tests/js/ummi-flow.test.js`).
  - Status timer disimpan per pengguna, kelas, dan tanggal di localStorage.
  - Bunyi pendek 1 menit sebelum sesi habis.
  - Saat waktu habis: bunyi, getar, dan notifikasi. Diulang tiap menit selama lewat waktu, sampai musyrif pindah sesi.
  - Layar dijaga tetap menyala (Wake Lock).
- Tes PHP: `tests/Feature/UmmiProsemTest.php`.
