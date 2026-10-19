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
- Halaman buku sebuah baris **dibagi rata** ke TM-nya. Sisa halaman diberikan ke TM awal. Contoh: hlm 1-8 dalam 4 TM menjadi 1-2, 3-4, 5-6, 7-8.
- **Peraga:** satu jilid (40 halaman buku) memakai dua peraga, masing-masing 20 halaman.
  - Jilid 1 memakai Peraga 1 & 2, Jilid 2 memakai Peraga 3 & 4, Jilid 3 memakai Peraga 5 & 6.
  - "18-4" berarti peraga pertama hlm 18-20, lalu peraga kedua hlm 1-4.
  - Baris berikutnya yang mulai dari halaman lebih kecil sudah berada di peraga kedua.
  - Peraga dihitung sebagai urutan 1-40 di dalam jilid (`peragaRanges()`, `peragaLabel()`), lalu dibagi rata per TM.
- Buku tidak mungkin melompat, jadi "9-1" di sheet dibaca sebagai 1-9 (dengan peringatan saat impor).
- Hafalan dan drill berlaku di semua TM baris itu. Baris "Dril"/"Ujian" adalah TM tanpa halaman.
- **Target per murid mengikuti jurnal Ummi, bukan kalender** (`studentProgress()`):
  - TM bermateri terpenuhi bila posisi buku di jurnal (Jilid + halaman, `UmmiProgressService::pageValue`) sudah sampai halaman akhir TM itu.
  - TM Drill/Ujian terpenuhi setelah sejumlah sesi jurnal tercatat sesudah materi terakhir yang terpenuhi. Untuk kelas pekanan, satu sesi menghabiskan semua TM pekan itu.
  - Target tidak bergeser sebelum terpenuhi. Capaian yang melewati target langsung memajukan target, dan dihitung sebagai surplus.
- **Alur Pembelajaran** memakai TM **mayoritas** murid halaqoh sebagai target sesi.
  - Murid di belakang mayoritas ditandai "Tertinggal n TM", dan musyrif bisa menekan **Samakan**. Data disimpan di `ummi_prosem_overrides`, dan target murid itu dimajukan ke TM mayoritas.
  - Murid di depan mayoritas ditandai "Surplus".
  - Kelas pekanan: target sesi = TM itu ditambah TM lain di pekan Prosem yang sama (`sessionTarget()`).
- `meetingFor()` (jadwal menurut kalender) hanya ditampilkan sebagai keterangan "jadwal Prosem pekan ini". Pekan ke-n dalam bulan memakai pekan Senin-Minggu, dengan pekan 1 memuat tanggal 1.

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
