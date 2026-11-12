<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\BoardingController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MedicalController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| Public Routes (Không yêu cầu Token)
|--------------------------------------------------------------------------
*/
Route::post('/v1/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Bắt buộc có Header Authorization: Bearer <token>)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // ==========================================
    // MODULE 1: XÁC THỰC & QUẢN LÝ NHÂN VIÊN
    // ==========================================
    Route::get('/users/me', [AuthController::class, 'me']);
    Route::put('/users/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Admin Management (Chỉ dành cho QUAN_TRI)
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
        Route::put('/users/{id}/status', [AdminUserController::class, 'updateStatus']);
    });

    // ==========================================
    // MODULE 2: QUẢN LÝ CHỦ NUÔI & THÚ CƯNG (CRUD)
    // ==========================================
    Route::apiResource('pets', PetController::class);
    Route::get('/owners', [PetController::class, 'owners']);

    // ==========================================
    // MODULE 3: LỒNG LƯU TRÚ & NỘI TRÚ
    // ==========================================
    Route::get('/boarding/cages', [BoardingController::class, 'index']);
    Route::post('/boarding/assign', [BoardingController::class, 'assignCage']);

    // ==========================================
    // MODULE 4: KHO THUỐC, VẬT TƯ & SPA (FEFO)
    // ==========================================
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/store', [InventoryController::class, 'store']);
    Route::post('/inventory/batch/import', [InventoryController::class, 'importBatch']);
    Route::post('/inventory/export', [InventoryController::class, 'exportMedicineFEFO']);

    // ==========================================
    // MODULE 5: KHÁM BỆNH, TIÊM PHÒNG & KÊ ĐƠN (mg/kg)
    // ==========================================
    Route::post('/medical/records', [MedicalController::class, 'store']);
    Route::get('/medical/records', [MedicalController::class, 'index']); // <--- BẠN CHỈ CẦN THÊM DÒNG NÀY
    Route::post('/medical/prescriptions', [MedicalController::class, 'createPrescription']);
    Route::get('/medical/history/pet/{petId}', [MedicalController::class, 'getHistoryByPet']);
    // ==========================================
    // MODULE 7: THANH TOÁN POS & DUYỆT XUẤT VIỆN
    // ==========================================
    Route::get('/payment/invoices', [PaymentController::class, 'index']);
    Route::get('/payment/invoices/{id}', [PaymentController::class, 'show']);
    Route::post('/payment/checkout', [PaymentController::class, 'createInvoiceAndCheckout']);

    // ==========================================
    // MODULE 8: DASHBOARD & ANALYTICS THỜI GIAN THỰC
    // ==========================================
    Route::get('/analytics/dashboard', [DashboardController::class, 'getOverview']);
    Route::get('/analytics/inventory-alerts', [DashboardController::class, 'getInventoryAlerts']);
    Route::get('/analytics/revenue', [DashboardController::class, 'getRevenueReport']);
});