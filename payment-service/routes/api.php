<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\PembayaranController;

/*
|--------------------------------------------------------------------------
| Payment & Tenancy Service API Routes
|--------------------------------------------------------------------------
|
| Protected routes require JWT middleware that validates
| tokens via the Auth Service.
|
*/

// Public routes (no JWT) — Payment Gateway Webhook
Route::post('/pembayaran/webhook', [PembayaranController::class, 'webhook']);

// Protected routes
Route::middleware([\App\Http\Middleware\JwtMiddleware::class])->group(function () {

    // Sewa routes
    Route::prefix('sewa')->group(function () {
        Route::get('/', [SewaController::class, 'index']);
        Route::post('/', [SewaController::class, 'store']);
        Route::get('/{id}', [SewaController::class, 'show']);
        Route::put('/{id}', [SewaController::class, 'update']);
        Route::delete('/{id}', [SewaController::class, 'destroy']);
    });

    // Tagihan routes
    Route::prefix('tagihan')->group(function () {
        Route::get('/', [TagihanController::class, 'index']);
        Route::post('/', [TagihanController::class, 'store']);
        Route::get('/{id}', [TagihanController::class, 'show']);
        Route::put('/{id}', [TagihanController::class, 'update']);
    });

    // Pembayaran routes
    Route::prefix('pembayaran')->group(function () {
        Route::get('/', [PembayaranController::class, 'index']);
        Route::post('/', [PembayaranController::class, 'store']);
        Route::get('/{id}', [PembayaranController::class, 'show']);
        Route::put('/{id}/status', [PembayaranController::class, 'updateStatus']);
    });
});
