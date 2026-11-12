<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CageOccupancyService
{
    /**
     * Đồng bộ số lượng thú thực tế và trạng thái lồng.
     *
     * DA_CO_THU nghĩa là lồng đang có ít nhất một thú.
     * Trạng thái đầy được xác định bằng soluonghientai >= succhua,
     * không lưu thành trạng thái riêng vì enum hiện tại không có giá trị DAY.
     * Trạng thái vệ sinh/bảo trì luôn được giữ nguyên.
     */
    public function sync(int $cageId): ?object
    {
        $cage = DB::table('cages')
            ->where('cage_id', $cageId)
            ->lockForUpdate()
            ->first();

        if (!$cage) {
            return null;
        }

        $actualCount = DB::table('pets')
            ->where('cage_id', $cageId)
            ->count();

        $status = in_array($cage->trangthai, ['DANG_VE_SINH', 'BAO_TRI'], true)
            ? $cage->trangthai
            : ($actualCount > 0 ? 'DA_CO_THU' : 'CON_TRONG');

        DB::table('cages')
            ->where('cage_id', $cageId)
            ->update([
                'soluonghientai' => $actualCount,
                'trangthai' => $status,
            ]);

        $cage->soluonghientai = $actualCount;
        $cage->trangthai = $status;

        return $cage;
    }
}
