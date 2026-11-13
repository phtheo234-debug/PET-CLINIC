<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSpaServiceController extends Controller
{
    public function store(Request $request)
    {
        $fields = $request->validate([
            'ten_sanpham' => 'required|string|max:100',
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
        ]);

        $service = DB::transaction(function () use ($fields) {
            $temporaryCode = 'TMP' . Str::upper(Str::random(16));
            $id = DB::table('inventory')->insertGetId([
                'masanpham' => $temporaryCode,
                'ten_sanpham' => $fields['ten_sanpham'],
                'danhmuc' => 'DICH_VU_SPA',
                'donvitinh' => $fields['donvitinh'],
                'dongia' => $fields['dongia'],
                'tonkhotoithieu' => 0,
                'lieu_mg_kg' => null,
                'mg_moi_don_vi' => null,
            ]);
            $code = 'SPA' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            while (DB::table('inventory')->where('masanpham', $code)->exists()) {
                $code = 'SPA' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . Str::upper(Str::random(4));
            }
            DB::table('inventory')->where('item_id', $id)->update(['masanpham' => $code]);

            return DB::table('inventory')->where('item_id', $id)->first();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Đã thêm dịch vụ Spa.',
            'data' => $service,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $service = DB::table('inventory')
            ->where('item_id', $id)
            ->where('danhmuc', 'DICH_VU_SPA')
            ->first();

        if (!$service) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy dịch vụ Spa.'], 404);
        }

        $fields = $request->validate([
            'ten_sanpham' => 'required|string|max:100',
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
        ]);

        DB::table('inventory')->where('item_id', $id)->update([
            'ten_sanpham' => $fields['ten_sanpham'],
            'donvitinh' => $fields['donvitinh'],
            'dongia' => $fields['dongia'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã cập nhật dịch vụ Spa.',
            'data' => DB::table('inventory')->where('item_id', $id)->first(),
        ]);
    }

    public function destroy(int $id)
    {
        return DB::transaction(function () use ($id) {
            $service = DB::table('inventory')
                ->where('item_id', $id)
                ->where('danhmuc', 'DICH_VU_SPA')
                ->lockForUpdate()
                ->first();

            if (!$service) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy dịch vụ Spa.'], 404);
            }

            if (DB::table('inventory_batches')->where('item_id', $id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa dịch vụ đã phát sinh lô dữ liệu kho.',
                ], 422);
            }

            DB::table('inventory')->where('item_id', $id)->delete();

            return response()->json(['status' => 'success', 'message' => 'Đã xóa dịch vụ Spa.']);
        });
    }
}
