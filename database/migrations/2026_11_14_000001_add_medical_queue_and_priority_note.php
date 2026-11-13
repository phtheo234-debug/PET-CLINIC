<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE medical_records MODIFY trangthai ENUM('CHUA_KHAM','DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') NOT NULL DEFAULT 'CHUA_KHAM'");
        DB::table('medical_records')->whereNull('invoice_id')->where('trangthai', 'HOAN_THANH')->update(['trangthai' => 'CHUA_KHAM']);
    }

    public function down(): void
    {
        DB::table('medical_records')->where('trangthai', 'CHUA_KHAM')->update(['trangthai' => 'DANG_DIEU_TRI']);
        DB::statement("ALTER TABLE medical_records MODIFY trangthai ENUM('DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') NOT NULL DEFAULT 'HOAN_THANH'");
    }
};
