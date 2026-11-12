<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password');

// Các trang cần đăng nhập mới được truy cập trực tiếp.
Route::middleware('web.token')->group(function () {
    Route::view('/change-password', 'auth.change-password')->name('change-password');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/pets', 'pets')->name('pets');
    Route::view('/cages', 'cages')->name('cages');
    Route::view('/medical', 'medical')->name('medical');
    Route::view('/inventory', 'inventory')->name('inventory');
    Route::view('/invoices', 'invoices')->name('invoices');

    // Chỉ quản trị viên được mở trang quản lý tài khoản.
    Route::view('/admin/users', 'admin.users')
        ->middleware('web.role:QUAN_TRI')
        ->name('admin.users');
});

// Giữ lại route test-api nếu nhóm vẫn dùng để kiểm tra nhanh.
Route::view('/test-api', 'test-api');
