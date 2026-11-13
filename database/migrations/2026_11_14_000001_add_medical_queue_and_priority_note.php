<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE medical_records MODIFY trangthai ENUM('CHUA_KHAM','DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') NOT NULL DEFAULT 'CHUA_KHAM'");
        if (!Schema::hasColumn('medical_records', 'ghi_chu_kham')) {
            Schema::table('medical_records', function ($table) {
                $table->text('ghi_chu_kham')->nullable()->after('trieuchung');
            });
        }
        DB::table('medical_records')->whereNull('invoice_id')->where('trangthai', 'HOAN_THANH')->update(['trangthai' => 'CHUA_KHAM']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_records', 'ghi_chu_kham')) {
            Schema::table('medical_records', function ($table) {
                $table->dropColumn('ghi_chu_kham');
            });
        }
        DB::statement("ALTER TABLE medical_records MODIFY trangthai ENUM('DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') NOT NULL DEFAULT 'HOAN_THANH'");
    }
};
