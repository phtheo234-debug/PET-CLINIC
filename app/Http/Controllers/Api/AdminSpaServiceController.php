<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminSpaServiceController extends Controller
{
    public function store(Request $request)
    {
        $fields = $request->validate([
            'masanpham' => 'required|string|max:20|unique:inventory,masanpham',
            'ten_sanpham' => 'required|string|max:100',
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
        ]);

        $id = DB::table('inventory')->insertGetId([
            'masanpham' => $fields['masanpham'],
            'ten_sanpham' => $fields['ten_sanpham'],
            'danhmuc' => 'DICH_VU_SPA',
            'donvitinh' => $fields['donvitinh'],
            'dongia' => $fields['dongia'],
            'tonkhotoithieu' => 0,
            'lieu_mg_kg' => null,
            'mg_moi_don_vi' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã thêm dịch vụ Spa.',
            'data' => DB::table('inventory')->where('item_id', $id)->first(),
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
            'masanpham' => ['required', 'string', 'max:20', Rule::unique('inventory', 'masanpham')->ignore($id, 'item_id')],
            'ten_sanpham' => 'required|string|max:100',
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
        ]);

        DB::table('inventory')->where('item_id', $id)->update([
            'masanpham' => $fields['masanpham'],
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
