# Laravel Microservices Architecture - Manajemen Kos

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)

Arsitektur microservices lengkap berbasis Laravel 12.x untuk sistem manajemen kos terdistribusi. Terdiri dari **5 microservices**, **1 API Gateway**, dan **manajemen database terpusat** dengan total 10 tabel relasional.
hello from fendew

---

## 🏗️ Arsitektur Sistem

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

---

## 📦 Layanan & Microservices

| Service | Port | Database Tables | Deskripsi |
|---------|------|-----------------|-----------|
| **API Gateway** | 8000 | — | Pintu masuk tunggal (single entry point), proxy routing ke service yang dituju, health checks. |
| **Auth Service** | 8001 | `users` | Registrasi owner, otentikasi JWT stateless, manajemen profil user, validasi token internal. |
| **Property Service** | 8002 | `kos`, `kamar`, `aset`, `maintenance` | Mengelola data kos, kamar, aset fasilitas kos, dan riwayat perbaikan/pemeliharaan aset. |
| **Payment Service** | 8003 | `sewa`, `tagihan`, `pembayaran` | Mengelola kontrak sewa penghuni, pembuatan tagihan bulanan, pencatatan pembayaran manual/gateway, webhook. |
| **Complaint Service** | 8005 | `laporankerusakan` | Pengaduan & pelaporan barang/fasilitas kos yang rusak oleh penghuni kos ke pengelola. |
| **Notification Service** | 8007 | `notifikasi` | Mengirim dan merekam notifikasi sistem (info, tagihan jatuh tempo, laporan kerusakan, dll) untuk user. |

---

## 📁 Struktur Folder Project

```text
Project-Laravel/
├── api-gateway/            # API Gateway (Proxy Controller)
├── auth-service/           # Auth & User Service (JWT Auth)
├── property-service/       # Property, Room & Asset Service
├── payment-service/        # Tenancy & Payment Service (Invoice & Webhook)
├── complaint-service/      # Complaint/Laporan Kerusakan Service
├── notification-service/   # Notification Service (Log Notifikasi)
├── database/               # Centralized Database Management (Migrations & Seeders)
│   ├── migrations/         # 11 File migrasi terpusat
│   └── seeders/            # Seeder kelas pengisian data awal
├── shared/                 # Shared PHP Helpers (MicroserviceClient, ServiceResponse)
├── docs/                   # Dokumentasi
│   ├── API_DOCUMENTATION.md                  # Dokumentasi API Lengkap
│   └── Microservices_Kos_API.postman_collection.json # Koleksi API Postman
├── migrate.bat             # Runner Script Migrasi Terpusat
├── start-services.bat      # Startup Script untuk Menjalankan Semua Service
└── README.md
```

---

## 🚀 Panduan Memulai Cepat (Quick Start)

### 1. Buat Database MySQL

```sql
CREATE DATABASE microservices_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Install Dependencies

Jalankan composer install di setiap folder service:
```bash
cd auth-service         && composer install
cd ../property-service && composer install
cd ../payment-service  && composer install
cd ../complaint-service && composer install
cd ../notification-service && composer install
cd ../api-gateway       && composer install
```

### 3. Konfigurasi Environment File (.env)

Pastikan kredensial database di `.env` setiap service sudah benar mengarah ke `microservices_db`.

### 4. Jalankan Migrasi & Seeder Terpusat

```bash
# Dari root directory
migrate.bat --fresh --seed
```

### 5. Jalankan Semua Service

Jalankan script batch untuk membuka 6 terminal/console sekaligus di Windows:
```bash
start-services.bat
```

### 6. Verifikasi & Health Check

```bash
curl http://localhost:8000/api/health
```

---

## 🔑 Akun Default (Seeded)

Gunakan akun-akun berikut untuk testing di Postman:

| Role | Email | Password |
|------|-------|----------|
| **Owner** | `owner@microservices.test` | `password123` |
| **Pengelola Kos** | `pengelola@microservices.test` | `password123` |
| **User (Penghuni)** | `ahmad@microservices.test` | `password123` |
| **User (Penghuni)** | `siti@microservices.test` | `password123` |

---

## 🔐 Alur Autentikasi JWT

1. Client login via `/api/auth/login` (Auth Service via Gateway).
2. Gateway mengembalikan response berisi JWT Token.
3. Client menyimpan token di local storage.
4. Client menyisipkan token pada header request berikutnya:
   `Authorization: Bearer <token_jwt>`
5. API Gateway menerima token, mencocokkan route, dan memforward request berserta token tersebut ke Service tujuan.
6. Service tujuan memvalidasi validitas token secara real-time dengan menembak internal API Auth Service (`/api/auth/validate-token`).

---

## 🔗 Komunikasi Antar Service (Inter-Service)

Komunikasi antar service berjalan secara sinkron (Synchronous) melalui HTTP Request dengan bantuan custom helper `MicroserviceClient` yang memiliki fitur auto-retry dan timeout.

**Contoh Alur Webhook Pembayaran:**
```
Payment Gateway Callback → API Gateway → Payment Service (:8003)
                                          │
                                          ├─(HTTP POST)─→ Notification Service (:8007)
                                          │               (Mengirim Notifikasi Sukses)
                                          ▼
                                   Response Sukses 200 OK
```

---

## 📖 Dokumentasi Pendukung

- [Dokumentasi API Detail & Contoh Request/Response](docs/API_DOCUMENTATION.md)
- [Koleksi Postman untuk Pengujian](docs/Microservices_Kos_API.postman_collection.json)
