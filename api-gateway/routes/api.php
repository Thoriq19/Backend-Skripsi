<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProxyController;

/*
|--------------------------------------------------------------------------
| API Gateway Routes
|--------------------------------------------------------------------------
|
| All client requests come through the Gateway.
| The Gateway forwards them to the appropriate microservice.
|
| Route mapping:
|   /api/auth/*         → Auth Service        (:8001)
|   /api/users/*        → Auth Service        (:8001)
|   /api/kos/*          → Property Service    (:8002)
|   /api/kamar/*        → Property Service    (:8002)
|   /api/aset/*         → Property Service    (:8002)
|   /api/maintenance/*  → Property Service    (:8002)
|   /api/sewa/*         → Payment Service     (:8003)
|   /api/tagihan/*      → Payment Service     (:8003)
|   /api/pembayaran/*   → Payment Service     (:8003)
|   /api/laporan/*      → Complaint Service   (:8005)
|   /api/notifikasi/*   → Notification Service(:8007)
|   /api/health         → Gateway Health Check
|
*/

// Health check (no auth required)
Route::get('/health', [ProxyController::class, 'health']);

// ─── Auth Service (:8001) ──────────────────────────────────────
Route::any('/auth/{path?}', [ProxyController::class, 'proxyAuth'])
    ->where('path', '.*');

Route::any('/users/{path?}', [ProxyController::class, 'proxyUsers'])
    ->where('path', '.*');

// ─── Property Service (:8002) ──────────────────────────────────
Route::any('/kos/{path?}', [ProxyController::class, 'proxyKos'])
    ->where('path', '.*');

Route::any('/kamar/{path?}', [ProxyController::class, 'proxyKamar'])
    ->where('path', '.*');

Route::any('/aset/{path?}', [ProxyController::class, 'proxyAset'])
    ->where('path', '.*');

Route::any('/maintenance/{path?}', [ProxyController::class, 'proxyMaintenance'])
    ->where('path', '.*');

// ─── Payment & Tenancy Service (:8003) ─────────────────────────
Route::any('/sewa/{path?}', [ProxyController::class, 'proxySewa'])
    ->where('path', '.*');

Route::any('/tagihan/{path?}', [ProxyController::class, 'proxyTagihan'])
    ->where('path', '.*');

Route::any('/pembayaran/{path?}', [ProxyController::class, 'proxyPembayaran'])
    ->where('path', '.*');

// ─── Complaint Service (:8005) ─────────────────────────────────
Route::any('/laporan/{path?}', [ProxyController::class, 'proxyLaporan'])
    ->where('path', '.*');

// ─── Notification Service (:8007) ──────────────────────────────
Route::any('/notifikasi/{path?}', [ProxyController::class, 'proxyNotifikasi'])
    ->where('path', '.*');
