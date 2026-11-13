<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('trangthai');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->nullOnDelete();
        });
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC', 'VAC_XIN', 'LUU_TRU', 'DICH_VU_SPA', 'PHI_KHAM') NOT NULL DEFAULT 'THUOC'");
    }

    public function down(): void
    {
        DB::statement("UPDATE invoice_details SET loai_chiphi = 'THUOC' WHERE loai_chiphi IN ('VAC_XIN', 'PHI_KHAM')");
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC', 'LUU_TRU', 'DICH_VU_SPA') NOT NULL DEFAULT 'THUOC'");
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
};
