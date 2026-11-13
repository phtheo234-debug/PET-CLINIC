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
            'masanpham' => ['required', 'string', 'max:50', 'regex:/^SPA-[0-9]{8}-[0-9]{6}$/', 'unique:inventory,masanpham'],
            'ten_sanpham' => 'required|string|max:100',
            'dongia' => 'required|numeric|min:0',
        ]);

        $service = DB::transaction(function () use ($fields) {
            $id = DB::table('inventory')->insertGetId([
                'masanpham' => $fields['masanpham'],
                'ten_sanpham' => $fields['ten_sanpham'],
                'danhmuc' => 'DICH_VU_SPA',
                'donvitinh' => '',
                'dongia' => $fields['dongia'],
                'tonkhotoithieu' => 0,
                'lieu_mg_kg' => null,
                'mg_moi_don_vi' => null,
            ]);
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
            'donvitinh' => 'nullable|string|max:20',
            'dongia' => 'required|numeric|min:0',
        ]);

        DB::table('inventory')->where('item_id', $id)->update([
            'ten_sanpham' => $fields['ten_sanpham'],
            'donvitinh' => '',
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
