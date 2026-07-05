<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Auth Service API Routes
|--------------------------------------------------------------------------
|
| Public routes (no authentication required)
|
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected routes (JWT authentication required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/validate-token', [AuthController::class, 'validateToken']);
    });

    // Owner-only: Create Pengelola Kos accounts
    Route::middleware('role:owner')->group(function () {
        Route::post('/users/create-pengelola-kos', [UserController::class, 'createPengelolaKos']);
    });

    // Pengelola Kos-only: Create User accounts
    Route::middleware('role:pengelola_kos')->group(function () {
        Route::post('/users/create-user', [UserController::class, 'createUser']);
    });

    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
});

