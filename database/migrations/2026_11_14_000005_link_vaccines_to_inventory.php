<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccination_types', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()
                ->after('is_active')
                ->constrained('inventory', 'item_id')
                ->nullOnDelete();
        });

        $names = ['Vắc-xin 1', 'Vắc-xin 2', 'Vắc-xin 3', 'Vắc-xin 4', 'Vắc-xin 5', 'Vắc-xin 6'];
        foreach ($names as $index => $name) {
            $type = DB::table('vaccination_types')->where('name', $name)->first();
            if (!$type) {
                continue;
            }

            $item = DB::table('inventory')->where('ten_sanpham', $name)
                ->where('danhmuc', 'VAC_XIN')->first();

            if (!$item) {
                do {
                    $code = 'VX-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                } while (DB::table('inventory')->where('masanpham', $code)->exists());

                $itemId = DB::table('inventory')->insertGetId([
                    'masanpham' => $code,
                    'ten_sanpham' => $name,
                    'danhmuc' => 'VAC_XIN',
                    'donvitinh' => 'liều',
                    'dongia' => 0,
                    'tonkhotoithieu' => 0,
                ]);
            } else {
                $itemId = $item->item_id;
            }

            DB::table('vaccination_types')->where('vaccination_type_id', $type->vaccination_type_id)
                ->update(['inventory_item_id' => $itemId]);
        }
    }

    public function down(): void
    {
        Schema::table('vaccination_types', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn('inventory_item_id');
        });
    }
};
