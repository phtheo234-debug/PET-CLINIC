<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedInteger('soluongton')->default(0)->after('dongia');
        });

        // Chuyển tồn đồ dùng cũ từ các lô sang số lượng tồn trực tiếp.
        DB::table('inventory')->where('danhmuc', 'DO_DUNG')->orderBy('item_id')->each(function ($item) {
            $quantity = DB::table('inventory_batches')->where('item_id', $item->item_id)->sum('soluong');
            DB::table('inventory')->where('item_id', $item->item_id)->update(['soluongton' => $quantity]);
            DB::table('inventory_batches')->where('item_id', $item->item_id)->delete();
        });

        // Giữ mã lô cũ nhưng thêm hậu tố ID cho các mã đang bị trùng trong cùng mặt hàng.
        $duplicateLots = DB::table('inventory_batches')
            ->select('item_id', 'solo')
            ->groupBy('item_id', 'solo')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        foreach ($duplicateLots as $duplicate) {
            $rows = DB::table('inventory_batches')
                ->where('item_id', $duplicate->item_id)
                ->where('solo', $duplicate->solo)
                ->orderBy('batch_id')
                ->skip(1)
                ->take(10000)
                ->get();
            foreach ($rows as $row) {
                DB::table('inventory_batches')->where('batch_id', $row->batch_id)
                    ->update(['solo' => substr($duplicate->solo, 0, 38) . '-' . $row->batch_id]);
            }
        }

        // Mã lô tự sinh; unique trong phạm vi từng mặt hàng.
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->unique(['item_id', 'solo'], 'inventory_batches_item_solo_unique');
        });

        // Dịch vụ không quản lý đơn vị tính.
        DB::table('inventory')->where('danhmuc', 'DICH_VU_SPA')->update(['donvitinh' => '']);
    }

    public function down(): void
    {
        DB::table('inventory')->where('danhmuc', 'DO_DUNG')->where('soluongton', '>', 0)->orderBy('item_id')->each(function ($item) {
            DB::table('inventory_batches')->insert([
                'item_id' => $item->item_id,
                'solo' => 'ROLLBACK-' . $item->item_id,
                'soluong' => $item->soluongton,
                'ngaynhap' => now()->toDateString(),
                'ngayhethan' => null,
                'trangthai' => 'DANG_SU_DUNG',
            ]);
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropUnique('inventory_batches_item_solo_unique');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn('soluongton');
        });
    }
};
