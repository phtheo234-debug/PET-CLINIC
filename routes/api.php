<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminCageController;
use App\Http\Controllers\Api\AdminInventoryController;
use App\Http\Controllers\Api\AdminSpaServiceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\BoardingController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MedicalController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SpaUsageController;

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
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'force.password.change'])->prefix('v1')->group(function () {
    Route::get('/users/me', [AuthController::class, 'me']);
    Route::put('/users/profile', [AuthController::class, 'updateProfile']);
    Route::put('/users/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // QUAN_TRI: quản lý tài khoản và danh mục hệ thống.
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

        Route::post('/spa-services', [AdminSpaServiceController::class, 'store']);
        Route::put('/spa-services/{id}', [AdminSpaServiceController::class, 'update']);
        Route::delete('/spa-services/{id}', [AdminSpaServiceController::class, 'destroy']);
    });

    // Hồ sơ thú cưng: mọi vai trò được xem; nhân viên/quản trị tiếp nhận và xóa;
    // cập nhật thông tin/cân nặng được phép cho cả bác sĩ.
    Route::get('/pets', [PetController::class, 'index']);
    Route::get('/pets/{pet}', [PetController::class, 'show']);
    Route::post('/pets', [PetController::class, 'store'])->middleware('role:QUAN_TRI,NHAN_VIEN');
    Route::put('/pets/{pet}', [PetController::class, 'update'])->middleware('role:QUAN_TRI,BAC_SI,NHAN_VIEN');
    Route::patch('/pets/{pet}', [PetController::class, 'update'])->middleware('role:QUAN_TRI,BAC_SI,NHAN_VIEN');
    Route::delete('/pets/{pet}', [PetController::class, 'destroy'])->middleware('role:QUAN_TRI,NHAN_VIEN');
    Route::get('/owners', [PetController::class, 'owners']);

    // Lồng: tất cả được xem; hệ thống đề xuất, nhân viên/quản trị xác nhận xếp lồng.
    Route::get('/boarding/cages', [BoardingController::class, 'index']);
    Route::get('/boarding/requests', [BoardingController::class, 'requests']);
    Route::post('/boarding/requests/{id}/confirm', [BoardingController::class, 'confirmRequest'])
        ->middleware('role:QUAN_TRI,NHAN_VIEN');

    // Ghi nhận Spa và thanh toán thuộc nghiệp vụ nhân viên/quản trị.
    Route::get('/spa/usages', [SpaUsageController::class, 'index'])->middleware('role:QUAN_TRI,NHAN_VIEN');
    Route::post('/spa/usages', [SpaUsageController::class, 'store'])->middleware('role:QUAN_TRI,NHAN_VIEN');

    // Danh mục kho cho phép xem; nhập/xuất kho dành cho nhân viên/quản trị.
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/batch/import', [InventoryController::class, 'importBatch'])->middleware('role:QUAN_TRI,NHAN_VIEN');
    Route::post('/inventory/export', [InventoryController::class, 'exportMedicineFEFO'])->middleware('role:QUAN_TRI,NHAN_VIEN');

    // Chỉ bác sĩ/quản trị được lập hồ sơ khám và kê đơn.
    Route::middleware('role:QUAN_TRI,BAC_SI')->group(function () {
        Route::post('/medical/records', [MedicalController::class, 'store']);
        Route::get('/medical/records', [MedicalController::class, 'index']);
        Route::post('/medical/prescriptions', [MedicalController::class, 'createPrescription']);
        Route::get('/medical/history/pet/{petId}', [MedicalController::class, 'getHistoryByPet']);
    });

    // Hóa đơn và xuất viện dành cho nhân viên/quản trị.
    Route::middleware('role:QUAN_TRI,NHAN_VIEN')->group(function () {
        Route::get('/payment/invoices', [PaymentController::class, 'index']);
        Route::get('/payment/invoices/{id}', [PaymentController::class, 'show']);
        Route::post('/payment/checkout', [PaymentController::class, 'createInvoiceAndCheckout']);
    });

    Route::get('/analytics/dashboard', [DashboardController::class, 'getOverview']);
    Route::get('/analytics/inventory-alerts', [DashboardController::class, 'getInventoryAlerts'])
        ->middleware('role:QUAN_TRI,NHAN_VIEN');
    Route::get('/analytics/revenue', [DashboardController::class, 'getRevenueReport'])
        ->middleware('role:QUAN_TRI');
});
