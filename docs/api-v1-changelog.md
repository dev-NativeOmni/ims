# HafizPlus 2.0 — API v1 Changelog

Dokumen ini mencatat riwayat perubahan, rilis, dan perbaikan untuk API v1 HafizPlus 2.0.

---

## [1.1.0] — 2026-06-02

### Added
*   **Token Management Endpoints**:
    *   `GET /api/v1/auth/tokens` untuk melihat daftar semua perangkat dan sesi token aktif milik user yang terhubung ke server.
    *   `POST /api/v1/auth/logout-other-devices` untuk mencabut semua sesi token aktif user kecuali perangkat yang sedang digunakan saat ini.
    *   `DELETE /api/v1/auth/tokens/{token}` untuk mencabut token tertentu berdasarkan ID token.
*   **Postman Collection**: Ekspor file koleksi Postman resmi di `docs/hafizplus-api-v1-postman.json` untuk mempermudah integrasi client. Request login dilengkapi skrip otomatis untuk memperbarui variabel environment token secara dinamis.
*   **Keamanan Token Mobile**: Penulisan panduan resmi penyimpanan token aman di mobile platform (`docs/mobile-storage-security.md`).

### Fixed
*   **SQLite Migration Conflict**: Memperbaiki indeks ganda kolom `auditable_type` dan `auditable_id` pada migrasi `create_audit_logs_table.php` yang menyebabkan kegagalan uji coba database SQLite (In-Memory).
*   **User Deletion Test**: Memperbaiki pengujian penghapusan akun di `ProfileTest.php` untuk mendukung fitur `SoftDeletes` pada model `User`.

---

## [1.0.0] — 2026-05-26

### Added
*   **Rilis Awal API v1**:
    *   **Authentication API**: Login Sanctum, logout, dan detail profil user (`/api/v1/auth/*`).
    *   **Dashboard API**: Summary dashboard yang dioptimalkan khusus untuk 4 role utama (Admin, Guru, Wali, dan Santri).
    *   **Quran API**: Readonly data Surah dan Ayat Al-Quran lengkap dengan pencarian dan filter juz.
    *   **Students API**: List, detail, dan ringkasan progres Quran santri.
    *   **Hafalan & Murajaah API**: List dan detail catatan setoran hafalan, catatan murajaah, serta target hafalan santri.
*   **Standardisasi Respons Error**: Struktur JSON error response seragam untuk status code `401`, `403`, `404`, `405`, `422`, `429`, dan `500`.
*   **Dokumentasi API Terperinci**:
    *   `docs/api-v1-reference.md` (Spesifikasi parameter & endpoint).
    *   `docs/api-v1-error-response.md` (Contoh format error).
    *   `docs/api-v1-browser-testing.md` (Panduan pengujian manual via Dev API Tester).
