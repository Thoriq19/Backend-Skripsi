<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotifikasiController;

/*
|--------------------------------------------------------------------------
| Notification Service API Routes
|--------------------------------------------------------------------------
|
| Routes are protected by JWT middleware that validates
| tokens via the Auth Service. The POST /api/notifikasi endpoint
| is also accessible from other services (inter-service calls).
|
*/
Route::prefix('notifikasi')->group(function () {
    // POST /api/notifikasi is open for inter-service calls
    Route::post('/', [NotifikasiController::class, 'store']);
    Route::middleware([\App\Http\Middleware\JwtMiddleware::class])->group(function () {
        Route::get('/', [NotifikasiController::class, 'index']);
        Route::get('/belum-dibaca', [NotifikasiController::class, 'unreadCount']);
        Route::get('/{id}', [NotifikasiController::class, 'show']);
        Route::put('/{id}/baca', [NotifikasiController::class, 'markAsRead']);
        Route::put('/baca-semua', [NotifikasiController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotifikasiController::class, 'destroy']);
    });
});
