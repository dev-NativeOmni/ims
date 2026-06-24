# HafizPlus 2.0 — API v1 Token Policy

Dokumen ini menjelaskan kebijakan token API v1 HafizPlus 2.0.

Token policy ini dipakai untuk mengatur:

- login API
- masa berlaku token
- batas token aktif per user
- revoke token
- logout device
- cleanup token expired
- handling token di mobile/frontend
- standar response auth/token

---

# 1. Scope

Token policy ini berlaku untuk endpoint API v1:

```text
/api/v1/*
```

Khususnya endpoint auth:

```text
POST   /api/v1/auth/login
GET    /api/v1/auth/me
GET    /api/v1/auth/tokens
POST   /api/v1/auth/logout
POST   /api/v1/auth/logout-all
POST   /api/v1/auth/logout-other-devices
DELETE /api/v1/auth/tokens/{token}
```

API menggunakan:

```text
Laravel Sanctum Personal Access Token
```

Format authorization:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

---

# 2. Policy Summary

| Policy | Nilai Default | Keterangan |
|---|---:|---|
| Token type | Bearer token | Laravel Sanctum personal access token |
| Token expiration | 30 hari | Token expired setelah 30 hari |
| Max active tokens/user | 5 token | Maksimal 5 device/session aktif |
| Login rate limit | 5 request/menit | Membatasi brute-force login |
| API rate limit | 60 request/menit | Membatasi request API umum |
| Logout current device | Ya | Revoke token yang sedang dipakai |
| Logout all devices | Ya | Revoke semua token user |
| Logout other devices | Ya | Revoke token lain, current token tetap aktif |
| Token list | Ya | User dapat melihat daftar token aktif |
| Token cleanup | Ya | Token expired dibersihkan oleh command/scheduler |
| Token abilities | Berdasarkan role | Scope token mengikuti role user |

---

# 3. Environment Configuration

Konfigurasi disimpan di `.env`.

```env
HAFIZPLUS_API_RATE_LIMIT_PER_MINUTE=60
HAFIZPLUS_API_LOGIN_RATE_LIMIT_PER_MINUTE=5
HAFIZPLUS_API_TOKEN_EXPIRATION_DAYS=30
HAFIZPLUS_API_MAX_ACTIVE_TOKENS_PER_USER=5
HAFIZPLUS_API_REVOKE_OLD_TOKENS_ON_LOGIN=false
HAFIZPLUS_API_TOKEN_NAME_PREFIX=hafizplus
HAFIZPLUS_CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173
```

## 3.1 Field Definition

| Key | Type | Default | Keterangan |
|---|---:|---:|---|
| `HAFIZPLUS_API_RATE_LIMIT_PER_MINUTE` | integer | `60` | Maksimal request API umum per menit |
| `HAFIZPLUS_API_LOGIN_RATE_LIMIT_PER_MINUTE` | integer | `5` | Maksimal percobaan login per email/IP per menit |
| `HAFIZPLUS_API_TOKEN_EXPIRATION_DAYS` | integer | `30` | Umur token dalam hari |
| `HAFIZPLUS_API_MAX_ACTIVE_TOKENS_PER_USER` | integer | `5` | Jumlah maksimal token aktif per user |
| `HAFIZPLUS_API_REVOKE_OLD_TOKENS_ON_LOGIN` | boolean | `false` | Jika `true`, login baru menghapus semua token lama |
| `HAFIZPLUS_API_TOKEN_NAME_PREFIX` | string | `hafizplus` | Prefix nama token |
| `HAFIZPLUS_CORS_ALLOWED_ORIGINS` | csv string | local origins | Origin frontend yang diizinkan |

---

# 4. Token Expiration

Default token API berlaku selama:

```text
30 hari
```

Nilai ini diatur oleh:

```env
HAFIZPLUS_API_TOKEN_EXPIRATION_DAYS=30
```

Saat login berhasil, API mengembalikan field:

```json
{
  "expires_at": "2026-06-25T10:00:00.000000Z"
}
```

Jika token sudah expired, API harus menolak request dengan status:

```text
401 Unauthorized
```

Response token expired:

```json
{
  "success": false,
  "message": "Token sudah kedaluwarsa. Silakan login ulang.",
  "errors": {
    "token": [
      "Token expired."
    ]
  },
  "status_code": 401
}
```

## 4.1 Catatan Production

Jangan gunakan token tanpa expiry di production.

Konfigurasi ini tidak disarankan:

```env
HAFIZPLUS_API_TOKEN_EXPIRATION_DAYS=0
```

Token tanpa expiry berisiko:

- tetap aktif walau device hilang
- sulit dikontrol saat akun bocor
- menumpuk di database
- memperbesar dampak jika token tercuri

---

# 5. Max Active Tokens Per User

Default maksimal token aktif per user:

```text
5 token
```

Konfigurasi:

```env
HAFIZPLUS_API_MAX_ACTIVE_TOKENS_PER_USER=5
```

Artinya satu user maksimal boleh punya 5 token aktif, misalnya:

| Device | Status |
|---|---|
| Android phone | Aktif |
| iPhone | Aktif |
| Tablet | Aktif |
| Browser admin | Aktif |
| API tester local | Aktif |

Jika user login lagi dan jumlah token melebihi batas, sistem boleh menghapus token lama berdasarkan policy implementasi.

## 5.1 Tujuan

Batas token aktif bertujuan untuk:

- mencegah token menumpuk
- membatasi risiko multi-device liar
- menjaga tabel `personal_access_tokens`
- mempermudah revoke saat akun dicurigai bocor

---

# 6. Revoke Old Tokens on Login

Konfigurasi:

```env
HAFIZPLUS_API_REVOKE_OLD_TOKENS_ON_LOGIN=false
```

## 6.1 Jika `false`

User bisa login dari beberapa device.

Token lama tetap aktif selama:

- belum expired
- belum melewati batas max active tokens
- belum di-revoke manual
- belum logout

Cocok untuk:

- mobile app
- multi-device usage
- parent/guru yang memakai beberapa perangkat

## 6.2 Jika `true`

Setiap login baru akan menghapus semua token lama milik user.

Cocok untuk mode security ketat.

Risiko:

- user akan logout dari device lain setiap kali login
- kurang nyaman untuk mobile multi-device

Rekomendasi HafizPlus:

```env
HAFIZPLUS_API_REVOKE_OLD_TOKENS_ON_LOGIN=false
```

Dengan catatan tetap memakai:

```env
HAFIZPLUS_API_MAX_ACTIVE_TOKENS_PER_USER=5
```

---

# 7. Token Abilities by Role

Token abilities adalah scope dasar token.

Catatan penting:

```text
Token abilities tidak menggantikan Policy/Gate.
```

Authorization tetap harus dicek di controller/service/policy berdasarkan:

- role user
- relasi student-parent
- relasi student-teacher
- ownership data
- status data

## 7.1 Default Abilities

| Role | Abilities |
|---|---|
| `super_admin` | `*` |
| `admin` | `read`, `write`, `admin` |
| `teacher` | `read`, `write:hafalan`, `write:murajaah`, `write:target` |
| `parent` | `read` |
| `student` | `read` |

## 7.2 Prinsip Authorization

Token abilities menjawab:

```text
Token ini secara umum boleh dipakai untuk apa?
```

Policy/Gate menjawab:

```text
User ini boleh mengakses data spesifik ini atau tidak?
```

Contoh:

Parent punya ability:

```text
read
```

Tetapi parent tetap tidak boleh membaca data semua santri. Parent hanya boleh membaca anak yang terhubung melalui relasi parent-student.

---

# 8. Endpoint: Login

```http
POST /api/v1/auth/login
```

## 8.1 Request Headers

```http
Accept: application/json
Content-Type: application/json
```

## 8.2 Request Body

```json
{
  "email": "admin@hafizplus.test",
  "password": "password123",
  "device_name": "Windows PowerShell"
}
```

## 8.3 Validation Rules

| Field | Rule |
|---|---|
| `email` | required, email, max:255 |
| `password` | required, string, max:255 |
| `device_name` | nullable, string, max:100 |

## 8.4 Success Response

```json
{
  "success": true,
  "message": "Login API berhasil.",
  "data": {
    "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "abilities": [
      "read",
      "write",
      "admin"
    ],
    "expires_at": "2026-06-25T10:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@hafizplus.test",
      "status": "active",
      "role": {
        "id": 2,
        "name": "admin",
        "label": "Admin"
      },
      "email_verified_at": null,
      "created_at": "2026-05-26T10:00:00.000000Z"
    }
  },
  "status_code": 200
}
```

## 8.5 Failed Login Response

```json
{
  "success": false,
  "message": "Email atau password tidak sesuai.",
  "errors": {
    "email": [
      "Email atau password tidak sesuai."
    ]
  },
  "status_code": 401
}
```

## 8.6 Inactive Account Response

```json
{
  "success": false,
  "message": "Akun tidak aktif atau role tidak diizinkan menggunakan API.",
  "errors": {
    "account": [
      "Akun tidak aktif atau role tidak valid untuk akses API."
    ]
  },
  "status_code": 403
}
```

---

# 9. Endpoint: Current User

```http
GET /api/v1/auth/me
```

## 9.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 9.2 Success Response

```json
{
  "success": true,
  "message": "Data user aktif berhasil diambil.",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@hafizplus.test",
      "status": "active",
      "role": {
        "id": 2,
        "name": "admin",
        "label": "Admin"
      },
      "email_verified_at": null,
      "created_at": "2026-05-26T10:00:00.000000Z"
    },
    "token": {
      "id": 10,
      "name": "hafizplus:Windows PowerShell",
      "abilities": [
        "read",
        "write",
        "admin"
      ],
      "last_used_at": "2026-05-26T10:10:00.000000Z",
      "expires_at": "2026-06-25T10:00:00.000000Z",
      "created_at": "2026-05-26T10:00:00.000000Z"
    }
  },
  "status_code": 200
}
```

---

# 10. Endpoint: List Active Tokens

```http
GET /api/v1/auth/tokens
```

## 10.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 10.2 Success Response

```json
{
  "success": true,
  "message": "Daftar token aktif berhasil diambil.",
  "data": {
    "tokens": [
      {
        "id": 10,
        "name": "hafizplus:Windows PowerShell",
        "abilities": [
          "read",
          "write",
          "admin"
        ],
        "is_current": true,
        "is_expired": false,
        "last_used_at": "2026-05-26T10:10:00.000000Z",
        "expires_at": "2026-06-25T10:00:00.000000Z",
        "created_at": "2026-05-26T10:00:00.000000Z",
        "updated_at": "2026-05-26T10:10:00.000000Z"
      }
    ]
  },
  "status_code": 200,
  "meta": {
    "total": 1,
    "max_active_tokens": 5,
    "token_expiration_days": 30
  }
}
```

## 10.3 Penggunaan

Endpoint ini dipakai untuk:

- melihat device/session aktif
- audit ringan oleh user
- mempersiapkan fitur revoke token tertentu
- debugging login API/mobile

---

# 11. Endpoint: Logout Current Device

```http
POST /api/v1/auth/logout
```

## 11.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 11.2 Success Response

```json
{
  "success": true,
  "message": "Logout API berhasil. Token aktif sudah dicabut.",
  "data": null,
  "status_code": 200
}
```

## 11.3 Efek

Hanya token yang sedang dipakai akan dihapus.

Setelah logout, token lama tidak bisa dipakai lagi.

Expected jika token lama dipakai:

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": [],
  "status_code": 401
}
```

---

# 12. Endpoint: Logout All Devices

```http
POST /api/v1/auth/logout-all
```

## 12.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 12.2 Success Response

```json
{
  "success": true,
  "message": "Semua token API berhasil dicabut.",
  "data": {
    "revoked_tokens": 5
  },
  "status_code": 200
}
```

## 12.3 Penggunaan

Endpoint ini dipakai ketika:

- user merasa akun bocor
- device hilang
- password diganti
- admin meminta user login ulang
- user ingin keluar dari semua perangkat

## 12.4 Efek

Semua token milik user akan dihapus, termasuk token yang sedang dipakai.

Setelah request sukses, user harus login ulang.

---

# 13. Endpoint: Logout Other Devices

```http
POST /api/v1/auth/logout-other-devices
```

## 13.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 13.2 Success Response

```json
{
  "success": true,
  "message": "Token device lain berhasil dicabut.",
  "data": {
    "revoked_tokens": 3
  },
  "status_code": 200
}
```

## 13.3 Efek

Token yang sedang dipakai tetap aktif.

Token lain milik user akan dihapus.

Cocok untuk:

- mengamankan akun tanpa logout dari device saat ini
- revoke session dari device lama
- mengurangi jumlah token aktif

---

# 14. Endpoint: Revoke Specific Token

```http
DELETE /api/v1/auth/tokens/{token}
```

## 14.1 Request Headers

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 14.2 Path Parameter

| Parameter | Type | Required | Keterangan |
|---|---|---|---|
| `token` | integer | yes | ID token milik user login |

## 14.3 Success Response

```json
{
  "success": true,
  "message": "Token berhasil dicabut.",
  "data": null,
  "status_code": 200
}
```

## 14.4 Token Not Found Response

```json
{
  "success": false,
  "message": "Token tidak ditemukan.",
  "errors": [],
  "status_code": 404
}
```

## 14.5 Security Rule

User hanya boleh revoke token miliknya sendiri.

User tidak boleh revoke token milik user lain melalui endpoint ini.

---

# 15. Token Cleanup Command

Token expired harus dibersihkan secara berkala dari database.

Command:

```powershell
php artisan hafizplus:prune-api-tokens
```

Dry run:

```powershell
php artisan hafizplus:prune-api-tokens --dry-run
```

Hapus token yang sudah expired minimal 7 hari:

```powershell
php artisan hafizplus:prune-api-tokens --days=7
```

## 15.1 Expected Output

Jika ada token expired:

```text
Deleted 10 expired API token(s).
```

Jika tidak ada:

```text
Deleted 0 expired API token(s).
```

## 15.2 Schedule

Token cleanup dijalankan harian:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('hafizplus:prune-api-tokens --days=7')
    ->dailyAt('02:00');
```

Artinya:

```text
Setiap hari pukul 02:00, sistem menghapus token yang expired minimal 7 hari.
```

---

# 16. Database

Token disimpan di tabel:

```text
personal_access_tokens
```

Kolom penting:

| Column | Keterangan |
|---|---|
| `id` | ID token |
| `tokenable_type` | Model pemilik token |
| `tokenable_id` | ID user pemilik token |
| `name` | Nama device/token |
| `token` | Hash token |
| `abilities` | Scope token |
| `last_used_at` | Waktu terakhir token dipakai |
| `expires_at` | Waktu token expired |
| `created_at` | Waktu token dibuat |
| `updated_at` | Waktu token diperbarui |

## 16.1 Catatan Penting

Nilai token asli hanya muncul sekali saat login.

Database hanya menyimpan hash token.

Jika user kehilangan token, solusinya adalah:

```text
login ulang
```

Bukan mengambil token lama dari database.

---

# 17. Mobile Client Handling

## 17.1 Simpan Token

Mobile app harus menyimpan token di secure storage.

Rekomendasi:

| Platform | Storage |
|---|---|
| Android | EncryptedSharedPreferences / Keystore |
| iOS | Keychain |
| Flutter | flutter_secure_storage |
| React Native | Keychain / Encrypted Storage |

Jangan simpan token di:

- plain text file
- local database tanpa enkripsi
- log
- hardcoded source code
- screenshot debugging
- analytics event

## 17.2 Saat Login Berhasil

Client harus menyimpan:

```json
{
  "access_token": "...",
  "token_type": "Bearer",
  "expires_at": "..."
}
```

## 17.3 Saat Request API

Client harus mengirim:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## 17.4 Saat Menerima 401

Jika API mengembalikan `401`, client harus:

1. Hapus token lokal.
2. Redirect user ke login.
3. Tampilkan pesan login ulang.
4. Jangan retry tanpa batas.

Contoh pesan:

```text
Sesi login sudah berakhir. Silakan login ulang.
```

## 17.5 Saat Logout

Client harus:

1. Panggil endpoint logout.
2. Hapus token dari secure storage.
3. Hapus cache data user.
4. Redirect ke login screen.

---

# 18. Browser/Frontend Handling

Untuk development browser, token boleh disimpan sementara di:

```text
localStorage
```

Tetapi untuk production app sensitif, localStorage tidak ideal karena rawan XSS.

Untuk HafizPlus, rekomendasi:

| Context | Storage |
|---|---|
| Local API tester | localStorage boleh |
| Internal admin dashboard existing Laravel session | session auth biasa |
| SPA production | pertimbangkan cookie-based Sanctum atau secure token strategy |
| Mobile app | secure storage wajib |

---

# 19. API Tester Policy

Halaman API tester:

```text
/dev/api-tester
```

hanya boleh aktif di environment:

```text
local
```

Route harus dibatasi:

```php
if (app()->environment('local')) {
    Route::get('/dev/api-tester', function () {
        return view('dev.api-tester');
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.api-tester');
}
```

Larangan:

- jangan publish API tester ke production
- jangan simpan token production di API tester
- jangan share screenshot token
- jangan commit token ke GitHub

---

# 20. Rate Limit

## 20.1 API General Rate Limit

Default:

```env
HAFIZPLUS_API_RATE_LIMIT_PER_MINUTE=60
```

Jika limit terlampaui:

```json
{
  "success": false,
  "message": "Terlalu banyak request. Silakan coba lagi beberapa saat.",
  "data": null,
  "errors": {
    "rate_limit": [
      "API rate limit exceeded."
    ]
  }
}
```

Status:

```text
429 Too Many Requests
```

## 20.2 Login Rate Limit

Default:

```env
HAFIZPLUS_API_LOGIN_RATE_LIMIT_PER_MINUTE=5
```

Jika limit login terlampaui:

```json
{
  "success": false,
  "message": "Percobaan login terlalu banyak. Silakan coba lagi beberapa saat.",
  "data": null,
  "errors": {
    "login": [
      "Login rate limit exceeded."
    ]
  }
}
```

Status:

```text
429 Too Many Requests
```

---

# 21. Security Rules

## 21.1 Token Confidentiality

Token harus dianggap seperti password.

Jangan pernah:

- mengirim token lewat chat publik
- menaruh token di screenshot
- commit token ke repository
- menyimpan token di file `.env.example`
- menampilkan token penuh di dashboard
- mencatat token penuh di log

## 21.2 Logging

Backend tidak boleh log:

```text
Authorization header
```

Backend tidak boleh log:

```text
plain text token
```

Jika perlu debugging, log hanya:

- user id
- token id
- request id
- endpoint
- timestamp
- IP
- user agent

## 21.3 Token Rotation

Untuk versi MVP, token rotation belum wajib.

Policy sementara:

```text
Saat token expired, user login ulang untuk mendapatkan token baru.
```

Rekomendasi fase lanjut:

- refresh token strategy
- device management UI
- revoke token dari dashboard user
- password change revoke all token
- admin force logout user

---

# 22. Password Change Policy

Saat user mengganti password, sistem sebaiknya melakukan:

```text
revoke all tokens
```

Alasan:

- password change biasanya sinyal security event
- token lama mungkin masih aktif di device lain
- user mengharapkan akun aman setelah password diganti

Status saat ini:

```text
Direkomendasikan untuk phase hardening berikutnya.
```

---

# 23. Admin Force Logout Policy

Admin/super admin sebaiknya bisa memaksa logout user tertentu jika:

- akun dicurigai bocor
- user resign/nonaktif
- device hilang
- role user berubah
- akses user dicabut

Status saat ini:

```text
Belum wajib untuk MVP API v1, tetapi direkomendasikan untuk production.
```

---

# 24. Error Handling Standard

Semua error token harus mengikuti format standar API v1:

```json
{
  "success": false,
  "message": "Pesan error.",
  "errors": {},
  "status_code": 401,
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-05-26T10:00:00.000000Z"
  }
}
```

## 24.1 Common Token Errors

| Status | Kondisi |
|---|---|
| `401` | Token kosong |
| `401` | Token invalid |
| `401` | Token expired |
| `401` | Token sudah logout/revoked |
| `403` | Akun tidak aktif |
| `403` | Role tidak boleh akses API |
| `404` | Token spesifik tidak ditemukan |
| `422` | Payload login tidak valid |
| `429` | Login/API rate limit |

---

# 25. PowerShell Testing

## 25.1 Login

```powershell
$base = "http://127.0.0.1:8000"

$login = Invoke-RestMethod `
  -Uri "$base/api/v1/auth/login" `
  -Method POST `
  -Headers @{
    "Accept" = "application/json"
    "Content-Type" = "application/json"
  } `
  -Body '{
    "email": "admin@hafizplus.test",
    "password": "password123",
    "device_name": "Windows PowerShell"
  }'

$token = $login.data.access_token

$headers = @{
  "Accept" = "application/json"
  "Authorization" = "Bearer $token"
}

$login
```

## 25.2 Current User

```powershell
Invoke-RestMethod `
  -Uri "$base/api/v1/auth/me" `
  -Method GET `
  -Headers $headers
```

## 25.3 List Active Tokens

```powershell
Invoke-RestMethod `
  -Uri "$base/api/v1/auth/tokens" `
  -Method GET `
  -Headers $headers
```

## 25.4 Logout Other Devices

```powershell
Invoke-RestMethod `
  -Uri "$base/api/v1/auth/logout-other-devices" `
  -Method POST `
  -Headers $headers
```

## 25.5 Logout Current Device

```powershell
Invoke-RestMethod `
  -Uri "$base/api/v1/auth/logout" `
  -Method POST `
  -Headers $headers
```

## 25.6 Verify Token Revoked

```powershell
Invoke-RestMethod `
  -Uri "$base/api/v1/auth/me" `
  -Method GET `
  -Headers $headers
```

Expected:

```text
401 Unauthorized
```

---

# 26. Acceptance Checklist

| Item | Status |
|---|---|
| Login menghasilkan Bearer token | ⬜ |
| Login response punya `expires_at` | ⬜ |
| Token tersimpan di `personal_access_tokens` | ⬜ |
| Token punya `abilities` sesuai role | ⬜ |
| `/auth/me` menampilkan current token | ⬜ |
| `/auth/tokens` menampilkan daftar token aktif | ⬜ |
| `/auth/logout` revoke current token | ⬜ |
| `/auth/logout-all` revoke semua token user | ⬜ |
| `/auth/logout-other-devices` revoke token lain | ⬜ |
| `DELETE /auth/tokens/{token}` hanya bisa hapus token milik sendiri | ⬜ |
| Token expired ditolak dengan `401` | ⬜ |
| Login rate limit berjalan | ⬜ |
| API rate limit berjalan | ⬜ |
| Cleanup command tersedia | ⬜ |
| Scheduler cleanup tersedia | ⬜ |
| Token tidak pernah dicatat penuh di log | ⬜ |
| API tester hanya aktif di local | ⬜ |

---

# 27. Recommended Next Improvements

Untuk production, tambahkan:

1. Revoke all token saat password berubah.
2. Device management UI untuk user.
3. Admin force logout user.
4. Audit log untuk login/logout/revoke token.
5. Notifikasi keamanan saat login device baru.
6. Refresh token strategy jika mobile butuh UX login panjang.
7. Alert jika login gagal berulang dari IP sama.

---

# 28. Final Rule

Token API HafizPlus harus diperlakukan sebagai kredensial sensitif.

Prinsip utama:

```text
Issue only when needed.
Expire automatically.
Limit active devices.
Revoke easily.
Never expose token in logs, screenshots, or repository.
```