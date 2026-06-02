# HafizPlus 2.0 — API v1 Pagination & Filtering Standards

Dokumen ini menjelaskan standar implementasi **Pagination (Paginasi)** dan **Filtering (Penyaringan data)** yang berlaku pada seluruh endpoint `GET` (Read-only) di API v1 HafizPlus 2.0. Standar ini harus diikuti oleh tim pengembang aplikasi mobile dan frontend client saat memanggil API.

---

## 1. Standar Paginasi (Pagination Standards)

Semua endpoint API v1 yang mengembalikan daftar data (list/collection) menggunakan paginasi berbasis halaman (*page-based pagination*) yang dikelola oleh Laravel Eloquent.

### 1.1 Parameter Request Paginasi
Pengembang client dapat mengirimkan parameter query berikut pada request `GET`:

| Query Parameter | Tipe Data | Default | Batasan / Validasi | Keterangan |
|---|---|---|---|---|
| `page` | integer | `1` | `min:1` | Nomor halaman yang ingin diambil. |
| `per_page` | integer | `15` | `min:1`, `max:50` | Jumlah data per halaman. Nilai di atas 50 otomatis dipotong menjadi 50 di sisi server. |

#### Contoh Request:
```http
GET /api/v1/students?page=2&per_page=20
```

### 1.2 Struktur Meta Respons Paginasi
Setiap respons paginasi yang sukses dibungkus di dalam field `meta.pagination` dengan struktur JSON sebagai berikut:

```json
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": {
    "items": []
  },
  "status_code": 200,
  "meta": {
    "pagination": {
      "current_page": 2,
      "from": 21,
      "last_page": 5,
      "per_page": 20,
      "to": 40,
      "total": 100
    }
  }
}
```

#### Penjelasan Field Meta:
*   `current_page`: Halaman aktif saat ini.
*   `from`: Indeks baris pertama dari data halaman saat ini dalam total keseluruhan data.
*   `last_page`: Halaman terakhir (total halaman tersedia).
*   `per_page`: Jumlah limit data per halaman yang digunakan.
*   `to`: Indeks baris terakhir dari data halaman saat ini dalam total keseluruhan data.
*   `total`: Total keseluruhan data di database yang cocok dengan filter pencarian.

---

## 2. Standar Penyaringan (Filtering Standards)

Untuk mempercepat pencarian data, API menyediakan fitur penyaringan dinamis menggunakan parameter query string.

### 2.1 Pencarian Global (`search`)
Hampir seluruh endpoint list mendukung pencarian global berbasis teks melalui parameter `search`. Di sisi server, pencarian ini menggunakan pencarian wildcard SQL `like` (`%kata-kunci%`).

*   **Aturan Validasi**: Maksimal 100 karakter, berupa string.
*   **Perilaku Pencarian**:
    *   *Santri*: Mencari nama santri, nomor santri (NIS), dan email.
    *   *Catatan Hafalan & Murajaah*: Mencari catatan tambahan (*notes*), nama santri, nomor santri, nama surah (latin/arab), serta nama/email guru pembimbing.

#### Contoh Request:
```http
GET /api/v1/students?search=Ahmad
```

### 2.2 Filter Tanggal Range (`from` & `to`)
Digunakan pada catatan transaksi seperti riwayat hafalan, murajaah, dan target hafalan untuk menyaring data berdasarkan rentang waktu tertentu.

*   **Format**: `YYYY-MM-DD` (Date ISO format standar).
*   **Parameter**:
    *   `from`: Tanggal awal pencarian (inklusif).
    *   `to`: Tanggal akhir pencarian (inklusif).
*   **Aturan Validasi**: `to` harus bernilai sama dengan atau setelah tanggal `from` (`after_or_equal:from`).

#### Contoh Request:
```http
GET /api/v1/hafalan-records?from=2026-05-01&to=2026-05-31
```

### 2.3 Filter Relasional & Enumerasi
Filter ini digunakan untuk menyaring data spesifik berdasarkan relasi database atau status data:

| Endpoint | Query Parameter | Keterangan / Nilai Valid |
|---|---|---|
| `/students` | `status` | `active` / `inactive` |
| `/students` | `class_room_id` | Menyaring santri di dalam kelas tertentu (integer) |
| `/students` | `teacher_id` | Menyaring santri di bawah bimbingan guru tertentu (integer) |
| `/hafalan-records` | `student_id` | Menyaring setoran milik santri tertentu (integer) |
| `/hafalan-records` | `status` | `passed` / `repeat` / `needs_improvement` |
| `/hafalan-records` | `submission_type` | `new` / `continuation` / `revision` |
| `/hafalan-targets` | `status` | `active` / `completed` / `missed` / `cancelled` |

---

## 3. Contoh Lengkap Panggilan API dengan Filter & Paginasi

Berikut adalah contoh pemanggilan riwayat hafalan santri dengan filter lengkap untuk santri tertentu, status lulus, rentang bulan Mei 2026, limit 10 data, halaman pertama:

### Request HTTP:
```http
GET /api/v1/hafalan-records?student_id=5&status=passed&from=2026-05-01&to=2026-05-31&per_page=10&page=1
Authorization: Bearer 1|xxxxxxxxxxxxxxxx
Accept: application/json
```

### Respons JSON Server:
```json
{
  "success": true,
  "message": "Data setoran hafalan berhasil diambil.",
  "data": {
    "hafalan_records": [
      {
        "id": 12,
        "student_id": 5,
        "student_name": "Ahmad Fauzi",
        "surah_name": "Al-Fatihah",
        "ayah_start": 1,
        "ayah_end": 7,
        "status": "passed",
        "score": "95.00",
        "submitted_at": "2026-05-15"
      }
    ]
  },
  "status_code": 200,
  "meta": {
    "pagination": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 10,
      "to": 1,
      "total": 1
    },
    "filters": {
      "student_id": 5,
      "teacher_id": null,
      "surah_id": null,
      "status": "passed",
      "submission_type": null,
      "from": "2026-05-01",
      "to": "2026-05-31",
      "search": ""
    }
  }
}
```
