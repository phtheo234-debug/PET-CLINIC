<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\BoardingController;

// Public Endpoint
Route::post('/v1/auth/login', [AuthController::class, 'login']);

// Authenticated Endpoints
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/users/me', [AuthController::class, 'me']);
    Route::put('/users/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/analytics/dashboard', [DashboardController::class, 'getOverview']);

    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
        Route::put('/users/{id}/status', [AdminUserController::class, 'updateStatus']);
    });
});

// CRUD Thú cưng
Route::apiResource('pets', PetController::class);

// Quản lý lưu trú & Xếp lồng
Route::post('/boarding/assign', [BoardingController::class, 'assignCage']);