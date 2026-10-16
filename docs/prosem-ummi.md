# Prosem Ummi & Alur Pembelajaran (Kelas 10)

Permintaan sekolah, Oktober 2026. Kode utama: `app/Services/UmmiProsemService.php`.

## Prosem (Program Semester)

- Tabel `ummi_prosem_rows`: satu baris = satu unit materi.
  - Isi baris: Jilid, halaman peraga, halaman buku, hafalan (ziyadah), drill (muraja'ah), dan keterangan "Drill"/"Ujian".
  - Kolom `weeks` berisi sebaran tatap muka (TM) per pekan, contohnya `{"7": {"4": 4}}` artinya pekan ke-4 Juli ada 4 TM.
- **Prosem per kelas & halaqoh (musyrif):** kolom `class_room_id` dan `teacher_id`.
  - Contoh: X E1 halaqoh Ust Fuad mulai Jilid 1, sedangkan halaqoh Ustadzah Isti mulai Jilid 2.
  - E2 dan E3 memakai 2 TM per pekan.
  - Urutan pemakaian untuk satu murid: Prosem kelas + musyrifnya, lalu Prosem kelasnya untuk semua halaqoh, lalu Prosem umum. Lihat `UmmiProsemService::resolveScope()`.
  - Murid tanpa Prosem tidak diberi target otomatis.
- Jumlah TM satu baris = jumlah sebaran pekannya.
- Halaman: **Target → tab Prosem Ummi** (`/prosem-ummi`). Pilih Kelas dan Halaqoh untuk melihat atau mengubah Prosem cakupan itu.
  - Semua staf bisa melihat.
  - Super admin, admin, dan koordinator tahfizh bisa mengubah.
- Impor satu halaqoh dari Google Sheet atau file Drive (Excel ikut diunduh). Tab yang dipakai adalah tab yang namanya memuat `--tab` (bawaan "prosem"):

  ```bash
  php artisan tad:prosem-import "<link sheet>" --kelas="X E2" --musyrif="Aiji" --tahun=2026/2027 --semester=1           # tampil
  php artisan tad:prosem-import "<link sheet>" --kelas="X E2" --musyrif="Aiji" --tahun=2026/2027 --semester=1 --simpan  # simpan
  ```

  - File harus dibagikan dengan akses "siapa saja yang memiliki link".
  - Nama surah dicocokkan dengan `App\Support\SurahNameMatcher`. Contoh: "Al Ashr" menjadi Al-'Asr, "Al qurois" menjadi Quraish, "An Nas-Al Ikhlas" dibaca sebagai rentang, dan "Ad Dhuha" menjadi Ad-Duha.
  - Baris yang tidak dikenali atau halaman yang terbalik (misalnya 18-4) muncul sebagai peringatan. Betulkan di halaman Prosem.

## Target per pertemuan

- `plan()` menyusun urutan TM semester: bulan → pekan 1-5 → urutan baris.
- Halaman peraga dan buku sebuah baris **dibagi rata** ke TM-nya. Sisa halaman diberikan ke TM awal. Contoh: hlm 1-8 dalam 4 TM menjadi 1-2, 3-4, 5-6, 7-8.
- Hafalan dan drill berlaku di semua TM baris itu.
- Rentang halaman terbalik tidak dibagi.
- Baris tanpa materi berarti TM pengulangan atau evaluasi.
- Pekan ke-n dalam bulan memakai pekan Senin-Minggu. Pekan 1 adalah pekan yang memuat tanggal 1.
- `meetingFor(kelas, tanggal, musyrif)` menentukan TM hari itu: pekan Prosem tanggal itu, ditambah urutan hari pertemuan Ummi kelas itu di pekan tersebut (Senin-Kamis, kalender akademik).
- **Kelas pekanan** (program "seminggu sekali"): semua TM di pekan itu digabung menjadi satu target. Contoh: TM 1-2, buku hlm 1-4.

## Target Ummi bulanan otomatis

- `monthTarget()` mengambil posisi TM terakhir sampai akhir bulan: Jilid, halaman buku, halaman peraga, dan surah hafalan terjauh. Untuk Juz 30, surah terjauh adalah nomor terkecil, karena urutan Ummi mundur dari An-Nas.
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
