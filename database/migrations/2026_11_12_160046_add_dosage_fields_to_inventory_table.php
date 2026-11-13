
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('lieu_mg_kg', 10, 2)
                ->nullable()
                ->after('dongia');

            $table->decimal('mg_moi_don_vi', 10, 2)
                ->nullable()
                ->after('lieu_mg_kg');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn([
                'lieu_mg_kg',
                'mg_moi_don_vi'
            ]);
        });
    }
};
