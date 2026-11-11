<?php

use Illuminate\Support\Facades\Route;

// Đổi view('welcome') thành view('test-api')
Route::get('/', function () {
    return view('test-api');
});