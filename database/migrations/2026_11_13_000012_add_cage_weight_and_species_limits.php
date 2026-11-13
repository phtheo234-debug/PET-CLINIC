<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cages', function (Blueprint $table) {
            $table->decimal('tai_trong_toi_da', 7, 2)->nullable()->after('succhua');
            $table->string('loai_ap_dung', 30)->nullable()->after('tai_trong_toi_da');
        });
    }

    public function down(): void
    {
        Schema::table('cages', function (Blueprint $table) {
            $table->dropColumn(['tai_trong_toi_da', 'loai_ap_dung']);
        });
    }
};