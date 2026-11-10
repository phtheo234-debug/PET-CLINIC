<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Thêm route này để mở trang test giao diện Bootstrap 4 đã tạo
Route::get('/test-ui', function () {
    return view('test-api');
});