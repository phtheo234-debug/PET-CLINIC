<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminCageController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\BoardingController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MedicalController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/v1/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetForgottenPassword'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
| Phải đăng nhập bằng Sanctum Bearer Token.
| Tài khoản cần đổi mật khẩu chỉ được xem thông tin cá nhân,
| đổi mật khẩu hoặc đăng xuất.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'force.password.change'])->prefix('v1')->group(function () {
    Route::get('/users/me', [AuthController::class, 'me']);
    Route::put('/users/profile', [AuthController::class, 'updateProfile']);
    Route::put('/users/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->middleware('role:QUAN_TRI')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
        Route::put('/users/{id}/status', [AdminUserController::class, 'updateStatus']);

        Route::post('/cages', [AdminCageController::class, 'store']);
        Route::put('/cages/{id}', [AdminCageController::class, 'update']);
        Route::delete('/cages/{id}', [AdminCageController::class, 'destroy']);

        Route::post('/inventory', [AdminInventoryController::class, 'store']);
        Route::put('/inventory/{id}', [AdminInventoryController::class, 'update']);
        Route::delete('/inventory/{id}', [AdminInventoryController::class, 'destroy']);

        Route::post('/cages', [AdminCageController::class, 'store']);
        Route::put('/cages/{id}', [AdminCageController::class, 'update']);
        Route::delete('/cages/{id}', [AdminCageController::class, 'destroy']);
    });

    Route::apiResource('pets', PetController::class);
    Route::get('/owners', [PetController::class, 'owners']);

    Route::get('/boarding/cages', [BoardingController::class, 'index']);
    Route::post('/boarding/assign', [BoardingController::class, 'assignCage']);
    Route::post('/boarding/release', [BoardingController::class, 'releaseCage']);

    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/store', [InventoryController::class, 'store']);
    Route::post('/inventory/batch/import', [InventoryController::class, 'importBatch']);
    Route::post('/inventory/export', [InventoryController::class, 'exportMedicineFEFO']);

    Route::post('/medical/records', [MedicalController::class, 'store']);
    Route::get('/medical/records', [MedicalController::class, 'index']);
    Route::post('/medical/prescriptions', [MedicalController::class, 'createPrescription']);
    Route::get('/medical/history/pet/{petId}', [MedicalController::class, 'getHistoryByPet']);

    Route::get('/payment/invoices', [PaymentController::class, 'index']);
    Route::get('/payment/invoices/{id}', [PaymentController::class, 'show']);
    Route::post('/payment/checkout', [PaymentController::class, 'createInvoiceAndCheckout']);

    Route::get('/analytics/dashboard', [DashboardController::class, 'getOverview']);
    Route::get('/analytics/inventory-alerts', [DashboardController::class, 'getInventoryAlerts']);
    Route::get('/analytics/revenue', [DashboardController::class, 'getRevenueReport']);
});
