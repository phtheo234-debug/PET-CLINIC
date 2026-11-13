<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('pets', 'qr_code')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->uuid('qr_code')->nullable()->unique()->after('mathucung');
            });
        }

        DB::table('pets')->whereNull('qr_code')->orderBy('pet_id')
            ->chunkById(100, function ($pets) {
                foreach ($pets as $pet) {
                    DB::table('pets')->where('pet_id', $pet->pet_id)
                        ->update(['qr_code' => (string) Str::uuid()]);
                }
            }, 'pet_id');

        if (Schema::hasColumn('pets', 'machip')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->dropUnique(['machip']);
                $table->dropColumn('machip');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pets', 'machip')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->string('machip', 50)->nullable()->unique();
            });
        }
        if (Schema::hasColumn('pets', 'qr_code')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->dropUnique(['qr_code']);
                $table->dropColumn('qr_code');
            });
        }
    }
};
