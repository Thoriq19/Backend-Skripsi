<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KosController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\MaintenanceController;

/*
|--------------------------------------------------------------------------
| Property & Room & Asset Service API Routes
|--------------------------------------------------------------------------
|
| All routes are protected by JWT middleware that validates
| tokens via the Auth Service.
|
*/
Route::middleware([\App\Http\Middleware\JwtMiddleware::class])->group(function () {

    // Kos routes
    Route::prefix('kos')->group(function () {
        Route::get('/', [KosController::class, 'index']);
        Route::post('/', [KosController::class, 'store']);
        Route::get('/{id}', [KosController::class, 'show']);
        Route::put('/{id}', [KosController::class, 'update']);
        Route::delete('/{id}', [KosController::class, 'destroy']);
    });

    // Kamar routes
    Route::prefix('kamar')->group(function () {
        Route::get('/', [KamarController::class, 'index']);
        Route::post('/', [KamarController::class, 'store']);
        Route::get('/{id}', [KamarController::class, 'show']);
        Route::put('/{id}', [KamarController::class, 'update']);
        Route::delete('/{id}', [KamarController::class, 'destroy']);
    });

    // Aset routes
    Route::prefix('aset')->group(function () {
        Route::get('/', [AsetController::class, 'index']);
        Route::post('/', [AsetController::class, 'store']);
        Route::get('/{id}', [AsetController::class, 'show']);
        Route::put('/{id}', [AsetController::class, 'update']);
        Route::delete('/{id}', [AsetController::class, 'destroy']);
    });

    // Maintenance routes
    Route::prefix('maintenance')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index']);
        Route::post('/', [MaintenanceController::class, 'store']);
        Route::get('/{id}', [MaintenanceController::class, 'show']);
        Route::put('/{id}', [MaintenanceController::class, 'update']);
        Route::delete('/{id}', [MaintenanceController::class, 'destroy']);
    });
});
