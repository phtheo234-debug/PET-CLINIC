<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cages', 'tai_trong_toi_da')) {
            Schema::table('cages', function (Blueprint $table) {
                $table->decimal('tai_trong_toi_da', 7, 2)->nullable()->after('succhua');
            });
        }

        if (!Schema::hasColumn('cages', 'loai_ap_dung')) {
            Schema::table('cages', function (Blueprint $table) {
                $table->string('loai_ap_dung', 30)->nullable()->after('tai_trong_toi_da');
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['tai_trong_toi_da', 'loai_ap_dung'],
            fn ($column) => Schema::hasColumn('cages', $column)
        ));

        if ($columns) {
            Schema::table('cages', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};