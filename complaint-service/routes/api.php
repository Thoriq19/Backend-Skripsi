<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaporanController;

/*
|--------------------------------------------------------------------------
| Complaint Service API Routes
|--------------------------------------------------------------------------
|
| All routes are protected by JWT middleware that validates
| tokens via the Auth Service.
|
*/
Route::middleware([\App\Http\Middleware\JwtMiddleware::class])->group(function () {

    // Laporan Kerusakan routes
    Route::prefix('laporan')->group(function () {
        Route::get('/', [LaporanController::class, 'index']);
        Route::post('/', [LaporanController::class, 'store']);
        Route::get('/{id}', [LaporanController::class, 'show']);
        Route::put('/{id}', [LaporanController::class, 'update']);
        Route::put('/{id}/status', [LaporanController::class, 'updateStatus']);
        Route::delete('/{id}', [LaporanController::class, 'destroy']);
    });
});
