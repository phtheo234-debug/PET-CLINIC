<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pets', 'qr_code')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->dropUnique(['qr_code']);
                $table->dropColumn('qr_code');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pets', 'qr_code')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->uuid('qr_code')->nullable()->unique()->after('mathucung');
            });
        }
    }
};
