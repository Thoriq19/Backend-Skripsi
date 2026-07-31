# Laravel Microservices - API Documentation

## Daftar Isi

1. [Arsitektur Sistem](#arsitektur-sistem)
2. [Setup & Instalasi](#setup--instalasi)
3. [Auth & User Service API](#auth--user-service-api)
4. [Property Service API](#property-service-api)
5. [Payment & Tenancy Service API](#payment--tenancy-service-api)
6. [Complaint Service API](#complaint-service-api)
7. [Notification Service API](#notification-service-api)
8. [API Gateway](#api-gateway)
9. [Inter-Service Communication](#inter-service-communication)
10. [Error Handling](#error-handling)
11. [Environment Variables](#environment-variables)
12. [Quick Test (cURL)](#quick-test-curl)

---

## Arsitektur Sistem

```
                        ┌───────────────┐
                        │    Client     │
                        │  (Frontend)   │
                        └──────┬────────┘
                               │ HTTP Request
                               ▼
                ┌──────────────────────────────────┐
                │        API GATEWAY (:8000)        │
                │  - Route Dispatching              │
                │  - Request Forwarding             │
                │  - Health Check                   │
                └──┬───────┬───────┬───────┬───────┬┘
                   │       │       │       │       │
                   ▼       ▼       ▼       ▼       ▼
              ┌────────┐┌──────┐┌──────┐┌──────┐┌──────────┐
              │ Auth   ││Proprt││Pay   ││Compl ││Notifikasi│
              │Service ││Servce││Servce││Servce││Service   │
              │(:8001) ││(:8002││(:8003││(:8005││(:8007)   │
              └───┬────┘└──┬───┘└──┬───┘└──┬───┘└────┬─────┘
                  │        │       │       │         │
                  └────────┴───────┼───────┴─────────┘
                                   ▼
                          ┌──────────────┐
                          │  MySQL DB    │
                          │microservices │
                          │    _db       │
                          └──────────────┘
```

### Technology Stack

| Komponen | Teknologi |
|----------|-----------|
| Framework | Laravel 12.x |
| PHP | 8.2+ |
| Auth | Stateless JWT (php-open-source-saver/jwt-auth) |
| HTTP Client | Guzzle (inter-service communication) |
| Database | MySQL (single shared instance: `microservices_db`) |
| API Gateway | Laravel (custom ProxyController) |

### Standard Response Format

Semua API response mengikuti format berikut:

```json
{
    "success": true|false,
    "message": "Pesan deskriptif",
    "data": { ... } | null,
    "errors": { ... } | null
}
```

### Database Schema (10 Tabel)

| # | Tabel | FK | Service |
|---|-------|-----|---------|
| 1 | `users` | — | Auth |
| 2 | `kos` | `id_user` → users | Property |
| 3 | `kamar` | `id_kos` → kos | Property |
| 4 | `aset` | `id_kos` → kos | Property |
| 5 | `maintenance` | `id_aset` → aset | Property |
| 6 | `sewa` | `id_user` → users, `id_kamar` → kamar | Payment |
| 7 | `tagihan` | `id_sewa` → sewa | Payment |
| 8 | `pembayaran` | `id_tagihan` → tagihan | Payment |
| 9 | `laporankerusakan` | `id_user` → users, `id_aset` → aset | Complaint |
| 10 | `notifikasi` | `id_user` → users | Notification |

---

## Setup & Instalasi

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+
- XAMPP / Laragon / similar

### Step 1: Buat Database

```sql
CREATE DATABASE microservices_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Konfigurasi Environment

Setiap service memiliki file `.env` masing-masing. Update kredensial database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=microservices_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 3: Install Dependencies

```bash
cd auth-service && composer install
cd ../property-service && composer install
cd ../payment-service && composer install
cd ../complaint-service && composer install
cd ../notification-service && composer install
cd ../api-gateway && composer install
```

### Step 4: Jalankan Migrasi Terpusat

```bash
# Dari folder root project
migrate.bat                 # Jalankan migrasi saja
migrate.bat --seed          # Migrasi + seeder
migrate.bat --fresh --seed  # Fresh migration + seeder (hapus & buat ulang)
```

Atau manual:
```bash
cd auth-service
php artisan migrate --path=../database/migrations
php artisan db:seed --class=Database\Seeders\DatabaseSeeder
```

### Step 5: Jalankan Semua Service

```bash
# Opsi 1: Gunakan startup script (buka 6 terminal otomatis)
start-services.bat

# Opsi 2: Jalankan manual (masing-masing di terminal terpisah)
cd auth-service         && php artisan serve --port=8001
cd property-service     && php artisan serve --port=8002
cd payment-service      && php artisan serve --port=8003
cd complaint-service    && php artisan serve --port=8005
cd notification-service && php artisan serve --port=8007
cd api-gateway          && php artisan serve --port=8000
```

### Step 6: Verifikasi

```bash
# Health check via Gateway
curl http://localhost:8000/api/health
```

### Test Credentials (Seeder)

| Role | Email | Password |
|------|-------|----------|
| Owner | `owner@microservices.test` | `password123` |
| Pengelola Kos | `pengelola@microservices.test` | `password123` |
| User (Penghuni) | `ahmad@microservices.test` | `password123` |
| User (Penghuni) | `siti@microservices.test` | `password123` |

---

## Auth & User Service API

**Base URL:** `http://localhost:8001` (direct) atau `http://localhost:8000` (via gateway)

### Hierarki Pembuatan Akun

```
┌──────────────────────────────────────────────────────────────┐
│                 HIERARKI PEMBUATAN AKUN                       │
│                                                              │
│  🌐 Public Register ──→ Owner ONLY                          │
│                           │                                  │
│  🔒 Owner ──→ Membuat akun Pengelola Kos                    │
│                    │                                         │
│  🔒 Pengelola Kos ──→ Membuat akun User (Penghuni)          │
└──────────────────────────────────────────────────────────────┘
```

| Aksi | Siapa yang Bisa | Endpoint | Auth? |
|------|-----------------|----------|-------|
| Register Owner | Publik (siapa saja) | `POST /api/auth/register` | ❌ Tidak |
| Buat Pengelola Kos | Owner | `POST /api/users/create-pengelola-kos` | ✅ Owner |
| Buat User/Penghuni | Pengelola Kos | `POST /api/users/create-user` | ✅ Pengelola Kos |

### Authentication Endpoints

#### POST /api/auth/register

Registrasi akun **owner** baru. Ini adalah satu-satunya endpoint registrasi publik.

**Request:**
```json
{
    "nama_user": "Owner Kos Budi",
    "email_user": "budi@example.com",
    "password_user": "password123",
    "password_user_confirmation": "password123",
    "nohp_user": "081234567890"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Owner registered successfully",
    "data": {
        "user": {
            "id": 1,
            "nama_user": "Owner Kos Budi",
            "email_user": "budi@example.com",
            "role": "owner",
            "nohp_user": "081234567890",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    },
    "errors": null
}
```

#### POST /api/auth/login

Login dan dapatkan JWT token.

**Request:**
```json
{
    "email_user": "owner@microservices.test",
    "password_user": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "nama_user": "Owner Kos Pak Budi",
            "email_user": "owner@microservices.test",
            "role": "owner"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    },
    "errors": null
}
```

#### POST /api/auth/logout 🔒

Logout dan invalidasi token.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "Successfully logged out",
    "data": null,
    "errors": null
}
```

#### GET /api/auth/me 🔒

Dapatkan profil user yang sedang login.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "User profile retrieved",
    "data": {
        "id": 1,
        "nama_user": "Owner Kos Pak Budi",
        "email_user": "owner@microservices.test",
        "role": "owner",
        "nohp_user": "081234567890"
    },
    "errors": null
}
```

#### POST /api/auth/refresh 🔒

Refresh JWT token.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "Token refreshed",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    },
    "errors": null
}
```

#### POST /api/auth/validate-token 🔒 (Internal)

Validasi JWT token (digunakan oleh service lain secara internal).

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "Token is valid",
    "data": {
        "user_id": 1,
        "email_user": "owner@microservices.test",
        "role": "owner",
        "nama_user": "Owner Kos Pak Budi"
    },
    "errors": null
}
```

### User Management Endpoints

#### POST /api/users/create-pengelola-kos 🔒👑 (Owner Only)

Buat akun pengelola kos. Hanya owner yang bisa mengakses endpoint ini.

**Headers:** `Authorization: Bearer {token}` (Token Owner)

**Request:**
```json
{
    "nama_user": "Pengelola Kos Ani",
    "email_user": "ani@example.com",
    "password_user": "password123",
    "password_user_confirmation": "password123",
    "nohp_user": "081234567891"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Pengelola Kos account created successfully",
    "data": {
        "id": 2,
        "nama_user": "Pengelola Kos Ani",
        "email_user": "ani@example.com",
        "role": "pengelola_kos",
        "nohp_user": "081234567891",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "errors": null
}
```

**Error Response (403) — Non-owner mencoba mengakses:**
```json
{
    "success": false,
    "message": "Forbidden. You do not have the required role to access this resource.",
    "data": null,
    "errors": {
        "role": "Required role: owner. Your role: pengelola_kos"
    }
}
```

#### POST /api/users/create-user 🔒🛡️ (Pengelola Kos Only)

Buat akun user/penghuni. Hanya pengelola kos yang bisa mengakses endpoint ini.

**Headers:** `Authorization: Bearer {token}` (Token Pengelola Kos)

**Request:**
> [!NOTE]
> Parameter `dokumen_pendukung` dapat dikirim dalam bentuk string path file relatif **atau** data URL Base64 gambar KTP asli (misal: `data:image/png;base64,...`). Jika dikirim sebagai Base64, backend akan otomatis men-decode-nya dan menyimpan file fisik aslinya ke dalam disk server.

```json
{
    "nama_user": "Ahmad Fauzi",
    "email_user": "ahmad@example.com",
    "password_user": "password123",
    "password_user_confirmation": "password123",
    "nohp_user": "081234567892",
    "dokumen_pendukung": "data:image/png;base64,iVBORw0KGgoAAA..."
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "User account created successfully",
    "data": {
        "id": 3,
        "nama_user": "Ahmad Fauzi",
        "email_user": "ahmad@example.com",
        "role": "user",
        "nohp_user": "081234567892",
        "dokumen_pendukung": "/uploads/ktp/ktp_66a1e3b2e5c89.png"
    },
    "errors": null
}
```

#### GET /api/users 🔒

List semua users (paginated).

**Query Parameters:** 

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| role | string | Filter berdasarkan role (`owner`, `pengelola_kos`, `user`/penghuni) |
| per_page | integer | Item per halaman (default: 15) |
| page | integer | Halaman saat ini |

#### GET /api/users/{id} 🔒

Dapatkan user berdasarkan ID.

#### PUT /api/users/{id} 🔒

Update user.

**Request:**
> [!NOTE]
> Parameter `dokumen_pendukung` juga dapat menerima data URL Base64 gambar KTP asli untuk mengganti/memperbarui dokumen KTP yang terunggah.

```json
{
    "nama_user": "Ahmad Updated",
    "nohp_user": "081234567899",
    "role": "user",
    "dokumen_pendukung": "data:image/png;base64,iVBORw0KGgoAAA..."
}
```

#### DELETE /api/users/{id} 🔒

Hapus user (soft delete).

---

## Property Service API

**Base URL:** `http://localhost:8002` (direct) atau `http://localhost:8000` (via gateway)

> Semua endpoint memerlukan JWT token: `Authorization: Bearer {token}`

### Kos Endpoints

#### GET /api/kos 🔒

List semua kos dengan optional filter.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_user | integer | Filter berdasarkan pemilik (owner) |
| id_pengelola | integer | Filter berdasarkan pengelola kos |
| per_page | integer | Item per halaman (default: 15) |

**Response (200):**
```json
{
    "success": true,
    "message": "Kos retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "nama_kos": "Kos Mawar Putih Cabang Bandung",
                "alamat_kos": "Jl. Mawar No. 10, Bandung",
                "id_user": 1,
                "kamar": [
                    {
                        "id": 1,
                        "nomor_kamar": "A101",
                        "kapasitas_kamar": 1,
                        "harga_kamar": "800000.00",
                        "status_kamar": "terisi"
                    }
                ]
            }
        ],
        "total": 2
    },
    "errors": null
}
```

#### POST /api/kos 🔒

Buat kos baru.

**Request:**
```json
{
    "nama_kos": "Kos Melati Cabang Jakarta",
    "alamat_kos": "Jl. Melati No. 15, Jakarta Selatan",
    "id_user": 1,
    "id_pengelola": null
}
```

#### GET /api/kos/{id} 🔒

Detail kos beserta daftar kamar dan aset.

#### PUT /api/kos/{id} 🔒

Update data kos.

**Request:**
```json
{
    "nama_kos": "Kos Melati Cabang Jakarta (Updated)",
    "alamat_kos": "Jl. Melati No. 15B, Jakarta Selatan",
    "id_pengelola": 2
}
```

#### DELETE /api/kos/{id} 🔒

Hapus kos (soft delete).

### Kamar Endpoints

#### GET /api/kamar 🔒

List semua kamar dengan optional filter.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_kos | integer | Filter berdasarkan kos |
| status_kamar | string | Filter: `tersedia`, `terisi`, `maintenance`, `tidak_tersedia`, `segera` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/kamar 🔒👑 (Owner Only)

Buat kamar baru (menambah alokasi fisik kamar). Hanya Owner yang berhak menambah alokasi kamar baru pada kos miliknya.

**Headers:** `Authorization: Bearer {token}` (Token Owner)

**Request:**
```json
{
    "nomor_kamar": "A103",
    "tipe_kamar": "premium",
    "kapasitas_kamar": 2,
    "harga_kamar": 1200000,
    "status_kamar": "tersedia",
    "deskripsi_kamar": "Kamar luas dengan AC dan kamar mandi dalam",
    "id_kos": 1
}
```

#### GET /api/kamar/{id} 🔒

Detail kamar.

#### PUT /api/kamar/{id} 🔒🛡️ (Owner & Pengelola)

Update detail kamar (nomor, tipe, harga, status, atau deskripsi). Dapat diakses oleh Owner maupun Pengelola Kos yang ditugasi mengelola properti tersebut.

**Headers:** `Authorization: Bearer {token}` (Token Owner / Pengelola Kos)

**Request:**
```json
{
    "nomor_kamar": "A103-AC",
    "tipe_kamar": "deluxe",
    "harga_kamar": 1300000,
    "status_kamar": "terisi"
}
```

#### DELETE /api/kamar/{id} 🔒👑 (Owner Only)

Hapus kamar fisik (soft delete). Hanya Owner yang berhak mengurangi alokasi kamar fisik.

**Headers:** `Authorization: Bearer {token}` (Token Owner)

### Aset Endpoints

#### GET /api/aset 🔒

List semua aset dengan optional filter.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_kos | integer | Filter berdasarkan kos |
| kondisi | string | Filter: `baik`, `rusak_ringan`, `rusak_berat` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/aset 🔒

Buat aset baru.

**Request:**
```json
{
    "nama_aset": "AC Daikin 1PK",
    "kategori": "elektronik",
    "tanggal_pembelian": "2024-01-15",
    "harga": 4500000,
    "kondisi": "baik",
    "id_kos": 1
}
```

#### GET /api/aset/{id} 🔒

Detail aset.

#### PUT /api/aset/{id} 🔒

Update aset.

#### DELETE /api/aset/{id} 🔒

Hapus aset (soft delete).

### Maintenance Endpoints

#### GET /api/maintenance 🔒

List semua maintenance dengan optional filter.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_aset | integer | Filter berdasarkan aset |
| status | string | Filter: `dijadwalkan`, `sedang_dikerjakan`, `selesai` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/maintenance 🔒

Buat jadwal maintenance baru.

**Request:**
```json
{
    "deskripsi": "Servis AC rutin tahunan",
    "biaya": 350000,
    "tanggal_perbaikan": "2024-06-15",
    "status": "dijadwalkan",
    "id_aset": 1
}
```

#### GET /api/maintenance/{id} 🔒

Detail maintenance.

#### PUT /api/maintenance/{id} 🔒

Update maintenance.

#### DELETE /api/maintenance/{id} 🔒

Hapus maintenance.

---

## Payment & Tenancy Service API

**Base URL:** `http://localhost:8003` (direct) atau `http://localhost:8000` (via gateway)

> Semua endpoint memerlukan JWT token kecuali webhook.

### Sewa Endpoints

#### GET /api/sewa 🔒

List semua data kontrak sewa.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_user | integer | Filter berdasarkan penyewa |
| id_kamar | integer | Filter berdasarkan kamar |
| status_sewa | string | Filter: `aktif`, `berakhir`, `dibatalkan` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/sewa 🔒

Buat kontrak sewa baru. Sistem sewa bersifat bergulir (recurring/rolling contract). `tanggal_keluar` bersifat opsional (nullable) dan baru diisi saat checkout. `harga_sewa` digunakan untuk mengunci harga sewa. Setelah sewa dibuat, tagihan pertama akan otomatis di-generate.

**Request:**
```json
{
    "tanggal_masuk": "2024-07-01",
    "tanggal_keluar": null,
    "status_sewa": "aktif",
    "harga_sewa": 1200000,
    "id_user": 3,
    "id_kamar": 1
}
```

#### GET /api/sewa/{id} 🔒

Detail kontrak sewa.

#### PUT /api/sewa/{id} 🔒

Update kontrak sewa.

#### DELETE /api/sewa/{id} 🔒

Hapus kontrak sewa (soft delete).

### Tagihan Endpoints

#### GET /api/tagihan 🔒

List semua tagihan.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_sewa | integer | Filter berdasarkan kontrak sewa |
| status_tagihan | string | Filter: `belum_bayar`, `lunas`, `terlambat` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/tagihan 🔒

Buat tagihan baru.

**Request:**
```json
{
    "bulan_tagihan": "2024-07",
    "tanggal_jatuhtempo": "2024-07-10",
    "jumlah_tagihan": 800000,
    "status_tagihan": "belum_bayar",
    "id_sewa": 1
}
```

#### GET /api/tagihan/{id} 🔒

Detail tagihan.

#### PUT /api/tagihan/{id} 🔒

Update tagihan.

### Pembayaran Endpoints

#### GET /api/pembayaran 🔒

List semua pembayaran.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_tagihan | integer | Filter berdasarkan tagihan |
| status_pembayaran | string | Filter: `pending`, `berhasil`, `gagal` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/pembayaran 🔒

Buat pembayaran baru.

**Request:**
```json
{
    "metode_pembayaran": "transfer_bank",
    "jumlah_bayar": 800000,
    "status_pembayaran": "pending",
    "payment_gateway": "manual",
    "id_tagihan": 1
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Pembayaran created successfully",
    "data": {
        "id": 1,
        "tanggal_bayar": null,
        "metode_pembayaran": "transfer_bank",
        "jumlah_bayar": "800000.00",
        "status_pembayaran": "pending",
        "payment_gateway": "manual",
        "external_id": null,
        "status_webhook": "waiting",
        "id_tagihan": 1,
        "created_at": "2024-07-01T00:00:00.000000Z"
    },
    "errors": null
}
```

#### GET /api/pembayaran/{id} 🔒

Detail pembayaran beserta data tagihan terkait.

#### PUT /api/pembayaran/{id}/status 🔒

Update status pembayaran. Jika status diubah ke `berhasil`, otomatis memperbarui status tagihan menjadi `lunas`.

**Request:**
```json
{
    "status_pembayaran": "berhasil"
}
```

**Nilai yang diterima:** `pending`, `berhasil`, `gagal`

#### POST /api/pembayaran/webhook ⚠️ (PUBLIC — Tanpa JWT)

Endpoint callback dari payment gateway (Xendit/Midtrans). Tidak memerlukan autentikasi JWT.

**Request:**
```json
{
    "external_id": "TXN-XENDIT-12345",
    "status": "PAID"
}
```

**Status mapping dari gateway:**

| Status Gateway | Status Internal |
|----------------|-----------------|
| `PAID` | `berhasil` |
| `SETTLED` | `berhasil` |
| `EXPIRED` | `gagal` |
| `FAILED` | `gagal` |

**Response (200):**
```json
{
    "success": true,
    "message": "Webhook processed successfully",
    "data": {
        "id": 1,
        "status_pembayaran": "berhasil",
        "status_webhook": "received",
        "tanggal_bayar": "2024-07-05T10:30:00.000000Z"
    },
    "errors": null
}
```

---

## Complaint Service API

**Base URL:** `http://localhost:8005` (direct) atau `http://localhost:8000` (via gateway)

> Semua endpoint memerlukan JWT token: `Authorization: Bearer {token}`

### Laporan Kerusakan Endpoints

#### GET /api/laporan 🔒

List semua laporan kerusakan.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_user | integer | Filter berdasarkan pelapor |
| id_aset | integer | Filter berdasarkan aset yang dilaporkan |
| status_laporan | string | Filter: `dilaporkan`, `diproses`, `selesai` |
| per_page | integer | Item per halaman (default: 15) |

#### POST /api/laporan 🔒

Buat laporan kerusakan baru.

**Request:**
```json
{
    "deskripsi": "AC di kamar A101 tidak dingin, hanya keluar angin biasa",
    "foto": "uploads/laporan/ac_rusak_001.jpg",
    "id_user": 3,
    "id_aset": 1
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Laporan created successfully",
    "data": {
        "id": 1,
        "tanggal_lapor": "2024-07-05T08:30:00.000000Z",
        "status_laporan": "dilaporkan",
        "deskripsi": "AC di kamar A101 tidak dingin, hanya keluar angin biasa",
        "foto": "uploads/laporan/ac_rusak_001.jpg",
        "id_user": 3,
        "id_aset": 1
    },
    "errors": null
}
```

#### GET /api/laporan/{id} 🔒

Detail laporan kerusakan.

#### PUT /api/laporan/{id} 🔒

Update laporan kerusakan.

#### PUT /api/laporan/{id}/status 🔒

Update status laporan kerusakan.

**Request:**
```json
{
    "status_laporan": "diproses"
}
```

**Nilai yang diterima:** `dilaporkan`, `diproses`, `selesai`

#### DELETE /api/laporan/{id} 🔒

Hapus laporan kerusakan.

---

## Notification Service API

**Base URL:** `http://localhost:8007` (direct) atau `http://localhost:8000` (via gateway)

> Semua endpoint memerlukan JWT token: `Authorization: Bearer {token}`

### Notifikasi Endpoints

#### GET /api/notifikasi 🔒

List semua notifikasi.

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id_user | integer | Filter berdasarkan penerima |
| tipe | string | Filter: `info`, `peringatan`, `pembayaran`, `laporan` |
| dibaca | boolean | Filter: `true` (sudah dibaca), `false` (belum dibaca) |
| per_page | integer | Item per halaman (default: 15) |

**Response (200):**
```json
{
    "success": true,
    "message": "Notifikasi retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "id_user": 3,
                "judul": "Pengingat Pembayaran",
                "pesan": "Tagihan bulan 2024-07 - 5 hari lagi jatuh tempo. Jumlah: Rp 800.000",
                "tipe": "peringatan",
                "dibaca": false,
                "id_terkait": 1,
                "tipe_terkait": "tagihan",
                "created_at": "2024-07-05T08:00:00.000000Z"
            }
        ],
        "total": 2
    },
    "errors": null
}
```

#### POST /api/notifikasi 🔒

Buat notifikasi baru (biasanya dipanggil oleh service lain secara internal).

**Request:**
```json
{
    "id_user": 3,
    "judul": "Pengingat Pembayaran",
    "pesan": "Tagihan bulan 2024-07 jatuh tempo 5 hari lagi",
    "tipe": "peringatan",
    "id_terkait": 1,
    "tipe_terkait": "tagihan"
}
```

#### GET /api/notifikasi/belum-dibaca 🔒

Hitung jumlah notifikasi yang belum dibaca.

**Query Parameters:** `?id_user=3`

**Response (200):**
```json
{
    "success": true,
    "message": "Unread count retrieved",
    "data": {
        "belum_dibaca": 5
    },
    "errors": null
}
```

#### GET /api/notifikasi/{id} 🔒

Detail notifikasi.

#### PUT /api/notifikasi/{id}/baca 🔒

Tandai satu notifikasi sebagai sudah dibaca.

**Response (200):**
```json
{
    "success": true,
    "message": "Notifikasi marked as read",
    "data": {
        "id": 1,
        "dibaca": true
    },
    "errors": null
}
```

#### PUT /api/notifikasi/baca-semua 🔒

Tandai semua notifikasi milik user sebagai sudah dibaca.

**Request:**
```json
{
    "id_user": 3
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "5 notifikasi marked as read",
    "data": {
        "updated_count": 5
    },
    "errors": null
}
```

#### DELETE /api/notifikasi/{id} 🔒

Hapus notifikasi.

---

## API Gateway

**Base URL:** `http://localhost:8000`

### Route Mapping

| Gateway Route | Target Service | Port |
|---------------|---------------|------|
| `/api/auth/*` | Auth Service | 8001 |
| `/api/users/*` | Auth Service | 8001 |
| `/api/kos/*` | Property Service | 8002 |
| `/api/kamar/*` | Property Service | 8002 |
| `/api/aset/*` | Property Service | 8002 |
| `/api/maintenance/*` | Property Service | 8002 |
| `/api/sewa/*` | Payment Service | 8003 |
| `/api/tagihan/*` | Payment Service | 8003 |
| `/api/pembayaran/*` | Payment Service | 8003 |
| `/api/laporan/*` | Complaint Service | 8005 |
| `/api/notifikasi/*` | Notification Service | 8007 |
| `/api/health` | Gateway (self) | 8000 |

### Health Check

#### GET /api/health

Cek kesehatan semua service.

**Response (200):**
```json
{
    "success": true,
    "message": "All services are healthy",
    "data": {
        "gateway": "healthy",
        "services": {
            "auth": {
                "status": "healthy",
                "status_code": 200,
                "url": "http://localhost:8001"
            },
            "property": {
                "status": "healthy",
                "status_code": 200,
                "url": "http://localhost:8002"
            },
            "payment": {
                "status": "healthy",
                "status_code": 200,
                "url": "http://localhost:8003"
            },
            "complaint": {
                "status": "healthy",
                "status_code": 200,
                "url": "http://localhost:8005"
            },
            "notification": {
                "status": "healthy",
                "status_code": 200,
                "url": "http://localhost:8007"
            }
        },
        "timestamp": "2024-07-01T12:00:00.000000Z"
    },
    "errors": null
}
```

---

## Inter-Service Communication

### Pattern Overview

Service berkomunikasi satu sama lain menggunakan HTTP (Guzzle client). Token JWT diteruskan antar service untuk menjaga konteks autentikasi.

### Validasi Token JWT via Auth Service

Service lain (Property, Payment, Complaint, Notification) memvalidasi token JWT dengan memanggil endpoint internal Auth Service:

```
Service Downstream                       Auth Service
     │                                        │
     │  POST /api/auth/validate-token         │
     │  Authorization: Bearer {token}         │
     │ ─────────────────────────────────────→  │
     │                                        │
     │  { success: true, data: {              │
     │      user_id, email_user, role,        │
     │      nama_user                         │
     │  }}                                    │
     │ ←─────────────────────────────────────  │
     │                                        │
```

### Scheduler: Notifikasi Jatuh Tempo Otomatis

Payment Service menjalankan scheduler harian yang mengirim notifikasi via HTTP ke Notification Service:

```
Payment Service                          Notification Service
     │                                        │
     │  [Scheduler: daily at 08:00]           │
     │  Cek tagihan status=belum_bayar        │
     │                                        │
     │  POST /api/notifikasi                  │
     │  { id_user, judul, pesan, tipe,        │
     │    id_terkait, tipe_terkait }           │
     │ ─────────────────────────────────────→  │
     │                                        │
     │  { success: true }                     │
     │ ←─────────────────────────────────────  │
     │                                        │
```

**Kondisi yang dicek:**

| Selisih Hari | Aksi |
|--------------|------|
| H-8 | Kirim notifikasi peringatan |
| H-5 | Kirim notifikasi peringatan |
| H-0 | Kirim notifikasi jatuh tempo |
| H+x (lewat) | Update status → `terlambat` + kirim notifikasi |

### MicroserviceClient (shared/MicroserviceClient.php)

Helper class untuk komunikasi antar service dengan fitur:
- **Automatic retry** (exponential backoff: 100ms, 200ms, 400ms...)
- **Timeout handling** (default 10 detik)
- **JWT token forwarding**

```php
$client = new MicroserviceClient('http://localhost:8007');
$client->post('/api/notifikasi', [
    'id_user' => 3,
    'judul'   => 'Pengingat Pembayaran',
    'pesan'   => 'Tagihan bulan Juli jatuh tempo 5 hari lagi',
    'tipe'    => 'peringatan',
]);
```

---

## Error Handling

### HTTP Status Codes

| Code | Keterangan |
|------|------------|
| 200 | Sukses |
| 201 | Berhasil dibuat |
| 400 | Bad Request |
| 401 | Unauthorized (token invalid/missing) |
| 403 | Forbidden (role tidak sesuai) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |
| 503 | Service Unavailable |

### Validation Error Response (422)

```json
{
    "success": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "email_user": ["The email user field is required."],
        "password_user": ["The password user must be at least 6 characters."]
    }
}
```

### Unauthorized Response (401)

```json
{
    "success": false,
    "message": "Token not provided",
    "data": null,
    "errors": null
}
```

### Forbidden Response (403)

```json
{
    "success": false,
    "message": "Forbidden. You do not have the required role to access this resource.",
    "data": null,
    "errors": {
        "role": "Required role: owner. Your role: user"
    }
}
```

### Service Unavailable (503)

```json
{
    "success": false,
    "message": "Auth service unavailable: Connection refused",
    "data": null,
    "errors": null
}
```

---

## Environment Variables

### Shared (Semua Service)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| DB_CONNECTION | Database driver | mysql |
| DB_HOST | Database host | 127.0.0.1 |
| DB_PORT | Database port | 3306 |
| DB_DATABASE | Nama database | microservices_db |
| DB_USERNAME | Username database | root |
| DB_PASSWORD | Password database | (kosong) |

### Auth Service (:8001)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| JWT_SECRET | JWT signing key | (generated) |
| JWT_ALGO | JWT algorithm | HS256 |
| JWT_TTL | Token TTL (menit) | 60 |
| PROPERTY_SERVICE_URL | URL Property Service | http://localhost:8002 |
| PAYMENT_SERVICE_URL | URL Payment Service | http://localhost:8003 |
| COMPLAINT_SERVICE_URL | URL Complaint Service | http://localhost:8005 |
| NOTIFICATION_SERVICE_URL | URL Notification Service | http://localhost:8007 |

### Property Service (:8002)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| AUTH_SERVICE_URL | URL Auth Service | http://localhost:8001 |
| PAYMENT_SERVICE_URL | URL Payment Service | http://localhost:8003 |
| COMPLAINT_SERVICE_URL | URL Complaint Service | http://localhost:8005 |
| NOTIFICATION_SERVICE_URL | URL Notification Service | http://localhost:8007 |

### Payment Service (:8003)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| AUTH_SERVICE_URL | URL Auth Service | http://localhost:8001 |
| PROPERTY_SERVICE_URL | URL Property Service | http://localhost:8002 |
| NOTIFICATION_SERVICE_URL | URL Notification Service | http://localhost:8007 |

### Complaint Service (:8005)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| AUTH_SERVICE_URL | URL Auth Service | http://localhost:8001 |
| NOTIFICATION_SERVICE_URL | URL Notification Service | http://localhost:8007 |

### Notification Service (:8007)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| AUTH_SERVICE_URL | URL Auth Service | http://localhost:8001 |

### API Gateway (:8000)

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| AUTH_SERVICE_URL | URL Auth Service | http://localhost:8001 |
| PROPERTY_SERVICE_URL | URL Property Service | http://localhost:8002 |
| PAYMENT_SERVICE_URL | URL Payment Service | http://localhost:8003 |
| COMPLAINT_SERVICE_URL | URL Complaint Service | http://localhost:8005 |
| NOTIFICATION_SERVICE_URL | URL Notification Service | http://localhost:8007 |

---

## Quick Test (cURL)

### 1. Login sebagai Owner
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email_user": "owner@microservices.test",
    "password_user": "password123"
  }'
```

### 2. Lihat Profil (gunakan token dari login)
```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 3. List Kos
```bash
curl http://localhost:8000/api/kos \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 4. Buat Kamar Baru
```bash
curl -X POST http://localhost:8000/api/kamar \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "nomor_kamar": "C301",
    "kapasitas_kamar": 2,
    "harga_kamar": 1500000,
    "status_kamar": "tersedia",
    "deskripsi_kamar": "Kamar premium dengan balkon",
    "id_kos": 1
  }'
```

### 5. Buat Kontrak Sewa
```bash
curl -X POST http://localhost:8000/api/sewa \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "tanggal_masuk": "2024-07-01",
    "tanggal_keluar": "2025-07-01",
    "status_sewa": "aktif",
    "id_user": 3,
    "id_kamar": 1
  }'
```

### 6. Buat Tagihan
```bash
curl -X POST http://localhost:8000/api/tagihan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "bulan_tagihan": "2024-07",
    "tanggal_jatuhtempo": "2024-07-10",
    "jumlah_tagihan": 800000,
    "status_tagihan": "belum_bayar",
    "id_sewa": 1
  }'
```

### 7. Buat Laporan Kerusakan
```bash
curl -X POST http://localhost:8000/api/laporan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "deskripsi": "AC di kamar A101 tidak dingin",
    "id_user": 3,
    "id_aset": 1
  }'
```

### 8. Cek Notifikasi Belum Dibaca
```bash
curl "http://localhost:8000/api/notifikasi/belum-dibaca?id_user=3" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 9. Health Check
```bash
curl http://localhost:8000/api/health
```

### 10. Jalankan Scheduler Jatuh Tempo (Manual)
```bash
cd payment-service
php artisan tagihan:check-jatuhtempo
```
