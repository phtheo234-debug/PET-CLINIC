<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/change-password', 'auth.change-password')->name('change-password');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password');
Route::view('/profile', 'profile')->name('profile');
Route::view('/admin/users', 'admin.users')->name('admin.users');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/pets', 'pets')->name('pets');
Route::view('/cages', 'cages')->name('cages');
Route::view('/medical', 'medical')->name('medical');
Route::view('/inventory', 'inventory')->name('inventory');
Route::view('/invoices', 'invoices')->name('invoices');

// Giữ lại route test-api nếu nhóm vẫn dùng để kiểm tra nhanh
Route::view('/test-api', 'test-api');
