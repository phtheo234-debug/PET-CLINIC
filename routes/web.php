<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetQrController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password');

// Trang tra cứu QR công khai: không tạo tài khoản cho chủ nuôi và không mở API quản trị.
Route::get('/pet-qr/{qrCode}', [PetQrController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('pets.qr.show');

Route::middleware('web.token')->group(function () {
    Route::view('/change-password', 'auth.change-password')->name('change-password');
    Route::view('/profile', 'profile')->name('profile');

    // Các vai trò đều được xem tổng quan, hồ sơ thú cưng và danh sách lồng.
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/pets', 'pets')->name('pets');
    Route::view('/cages', 'cages')->name('cages');

    // Bác sĩ và quản trị viên được truy cập nghiệp vụ khám, chẩn đoán, kê đơn.
    Route::view('/medical', 'medical')
        ->middleware('web.role:QUAN_TRI,BAC_SI')
        ->name('medical');

    // Cả ba vai trò được xem danh mục kho; thao tác nhập/xuất và quản lý
    // danh mục được kiểm soát riêng tại API.
    Route::view('/inventory', 'inventory')->name('inventory');

    // Thanh toán, xuất viện và ghi nhận Spa dành cho nhân viên/quản trị.
    Route::view('/invoices', 'invoices')
        ->middleware('web.role:QUAN_TRI,NHAN_VIEN')
        ->name('invoices');

    Route::view('/admin/users', 'admin.users')
        ->middleware('web.role:QUAN_TRI')
        ->name('admin.users');

    Route::view('/admin/statistics', 'admin.statistics')
        ->middleware('web.role:QUAN_TRI')
        ->name('admin.statistics');
});

Route::view('/test-api', 'test-api');
