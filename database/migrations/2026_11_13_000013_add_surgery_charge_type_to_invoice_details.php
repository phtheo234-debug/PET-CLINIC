<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC','VAC_XIN','LUU_TRU','DICH_VU_SPA','PHI_KHAM','THUC_AN','DO_DUNG','PHAU_THUAT') NOT NULL DEFAULT 'THUOC'");
    }

    public function down(): void
    {
        DB::table('invoice_details')->where('loai_chiphi', 'PHAU_THUAT')->update(['loai_chiphi' => 'PHI_KHAM']);
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC','VAC_XIN','LUU_TRU','DICH_VU_SPA','PHI_KHAM','THUC_AN','DO_DUNG') NOT NULL DEFAULT 'THUOC'");
    }
};