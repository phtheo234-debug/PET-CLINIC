<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC', 'LUU_TRU', 'DICH_VU_SPA') NOT NULL DEFAULT 'THUOC'");
    }

    public function down(): void
    {
        DB::statement("UPDATE invoice_details SET loai_chiphi = 'LUU_TRU' WHERE loai_chiphi = 'DICH_VU_SPA'");
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC', 'LUU_TRU') NOT NULL DEFAULT 'THUOC'");
    }
};
