# Tabungan Baris Tahfizh (Kelas 11 & 12)

Keputusan sekolah, Oktober 2026. Kode: `app/Services/LineSavingsService.php`.

## Aturan

| Hal | Aturan |
|---|---|
| Yang ditabung | Semua kelebihan **baris ayat baru** di atas target baris triwulan, apa pun posisi surah:ayat terhadap target guru. |
| Hitungan baris | Sama dengan rapor: hanya ayat baru. Ulangan, setoran ganda, dan ayat sambungan **tidak** dihitung (`HafalanProgressService::passedLineDetails`). |
| Memakai | Triwulan yang barisnya kurang memakai tabungan **hanya bila saldo cukup menutup seluruh kekurangan**, sehingga murid menjadi Tuntas. Bila tidak cukup, saldo tetap utuh. |
| Saldo maksimal | Satu target baris triwulan berjalan (`LineSavingsService::SAVINGS_CAP_TERMS`). |
| Masa berlaku | Sampai lulus: terbawa dari kelas 11 ke kelas 12. Dihitung sejak `AcademicYear::FIRST`. |
| Program | Reguler dan Tahfizh. Triwulan saat murid di Kelas 10/Ummi dilewati (Ummi tidak dinilai dengan baris). |

## Cara kerja

- Saldo **tidak disimpan** di database. Saldo dihitung ulang dari riwayat setoran setiap triwulan sebelumnya, dengan evaluasi yang sama dengan rapor (`termBreakdown(..., withSavings: false)`). Akibatnya, membetulkan setoran lama otomatis mengubah saldo.
- Tabungan diterapkan di satu tempat: `HafalanProgressService::termBreakdown()` menambah `evaluation.savings` (`before`, `used`, `deposit`, `after`) dan `evaluation.reached` menjadi Tuntas bila tabungan dipakai. Karena itu rapor, Laporan Triwulan, Target Triwulan, halaman Wali Kelas, dan perintah `tad:*` memakai status yang sama.
- Rapor yang sudah dikunci menyimpan angka saat dikunci dan tidak ikut berubah.

## Tampilan

- Rapor cetak: **tidak ada keterangan tabungan** (permintaan sekolah); hanya status Tuntas yang ikut tabungan.
- Laporan Triwulan, tabel Term: di bawah baris tampil `+2 dari tabungan · sisa 13` / `+15 ditabung · saldo 15`.

## Mengubah aturan

- Batas saldo: ubah `SAVINGS_CAP_TERMS`.
- Masa berlaku per tahun ajaran: batasi `termsBefore()` ke tahun ajaran `$termStart`.
- Tes: `tests/Feature/LineSavingsTest.php`.
