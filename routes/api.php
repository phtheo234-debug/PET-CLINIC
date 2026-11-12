
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
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Đăng nhập
Route::post('/v1/auth/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
| Yêu cầu đăng nhập bằng Sanctum Bearer Token
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // ==========================================
    // MODULE 1: XÁC THỰC
    // ==========================================

    Route::get('/users/me', [AuthController::class, 'me']);

    Route::put(
        '/users/change-password',
        [AuthController::class, 'changePassword']
    );

    Route::post('/auth/logout', [AuthController::class, 'logout']);


    // ==========================================
    // MODULE 1.1: QUẢN LÝ TÀI KHOẢN QUẢN TRỊ
    // Chỉ QUAN_TRI được phép truy cập
    // ==========================================

    Route::prefix('admin')
        ->middleware('role:QUAN_TRI')
        ->group(function () {

            // Danh sách tài khoản
            Route::get('/users', [
                AdminUserController::class,
                'index'
            ]);

            // Chi tiết tài khoản
            Route::get('/users/{id}', [
                AdminUserController::class,
                'show'
            ]);

            // Tạo tài khoản nhân viên
            Route::post('/users', [
                AdminUserController::class,
                'store'
            ]);

            // Cấp lại mật khẩu
            Route::put('/users/{id}/reset-password', [
                AdminUserController::class,
                'resetPassword'
            ]);

            // Cập nhật trạng thái tài khoản
            Route::put('/users/{id}/status', [
                AdminUserController::class,
                'updateStatus'
            ]);
        });


    // ==========================================
    // MODULE 2: QUẢN LÝ CHỦ NUÔI VÀ THÚ CƯNG
    // ==========================================

    Route::apiResource('pets', PetController::class);

    Route::get('/owners', [
        PetController::class,
        'owners'
    ]);


    // ==========================================
    // MODULE 3: LỒNG LƯU TRÚ & NỘI TRÚ
    // ==========================================

    // Lấy danh sách lồng và thú cưng đang lưu trú
    Route::get('/boarding/cages', [
        BoardingController::class,
        'index'
    ]);

    // Xếp lồng cho thú cưng
    Route::post('/boarding/assign', [
        BoardingController::class,
        'assignCage'
    ]);

    // Trả lồng / Kết thúc đợt lưu trú
    Route::post('/boarding/release', [
        BoardingController::class,
        'releaseCage'
    ]);


    // ==========================================
    // MODULE 4: KHO THUỐC VÀ VẬT TƯ
    // ==========================================

    Route::get('/inventory', [
        InventoryController::class,
        'index'
    ]);

    Route::post('/inventory/store', [
        InventoryController::class,
        'store'
    ]);

    Route::post('/inventory/batch/import', [
        InventoryController::class,
        'importBatch'
    ]);

    Route::post('/inventory/export', [
        InventoryController::class,
        'exportMedicineFEFO'
    ]);


    // ==========================================
    // MODULE 5: KHÁM BỆNH VÀ KÊ ĐƠN
    // ==========================================

    Route::post('/medical/records', [
        MedicalController::class,
        'store'
    ]);

    Route::get('/medical/records', [
        MedicalController::class,
        'index'
    ]);

    Route::post('/medical/prescriptions', [
        MedicalController::class,
        'createPrescription'
    ]);

    Route::get('/medical/history/pet/{petId}', [
        MedicalController::class,
        'getHistoryByPet'
    ]);


    // ==========================================
    // MODULE 7: THANH TOÁN VÀ XUẤT VIỆN
    // ==========================================

    Route::get('/payment/invoices', [
        PaymentController::class,
        'index'
    ]);

    Route::get('/payment/invoices/{id}', [
        PaymentController::class,
        'show'
    ]);

    Route::post('/payment/checkout', [
        PaymentController::class,
        'createInvoiceAndCheckout'
    ]);


    // ==========================================
    // MODULE 8: DASHBOARD VÀ THỐNG KÊ
    // ==========================================

    Route::get('/analytics/dashboard', [
        DashboardController::class,
        'getOverview'
    ]);

    Route::get('/analytics/inventory-alerts', [
        DashboardController::class,
        'getInventoryAlerts'
    ]);

    Route::get('/analytics/revenue', [
        DashboardController::class,
        'getRevenueReport'
    ]);

});
