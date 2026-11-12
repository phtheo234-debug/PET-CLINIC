<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCageController extends Controller
{
    public function __construct(private CageOccupancyService $cageOccupancy)
    {
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'malong' => 'required|string|max:20|unique:cages,malong',
            'ten_long' => 'required|string|max:50',
            'loailong' => ['required', Rule::in(['THUONG', 'CAP_CUU_ICU', 'CACH_LY'])],
            'succhua' => 'required|integer|min:1|max:100',
            'trangthai' => ['nullable', Rule::in(['CON_TRONG', 'DANG_VE_SINH', 'BAO_TRI'])],
        ]);

        $id = DB::table('cages')->insertGetId([
            'malong' => trim($fields['malong']),
            'ten_long' => trim($fields['ten_long']),
            'loailong' => $fields['loailong'],
            'succhua' => $fields['succhua'],
            'soluonghientai' => 0,
            'trangthai' => $fields['trangthai'] ?? 'CON_TRONG',
        ], 'cage_id');

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm lồng lưu trú thành công!',
            'data' => DB::table('cages')->where('cage_id', $id)->first(),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $fields = $request->validate([
            'malong' => ['required', 'string', 'max:20', Rule::unique('cages', 'malong')->ignore($id, 'cage_id')],
            'ten_long' => 'required|string|max:50',
            'loailong' => ['required', Rule::in(['THUONG', 'CAP_CUU_ICU', 'CACH_LY'])],
            'succhua' => 'required|integer|min:1|max:100',
            'trangthai' => ['required', Rule::in(['CON_TRONG', 'DA_CO_THU', 'DANG_VE_SINH', 'BAO_TRI'])],
        ]);

        return DB::transaction(function () use ($fields, $id) {
            $cage = DB::table('cages')->where('cage_id', $id)->lockForUpdate()->first();
            if (!$cage) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy lồng.'], 404);
            }

            $actualCount = DB::table('pets')->where('cage_id', $id)->count();
            if ((int) $fields['succhua'] < $actualCount) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Sức chứa mới không được nhỏ hơn số thú đang ở trong lồng ({$actualCount}).",
                ], 422);
            }

            if ($actualCount > 0 && in_array($fields['trangthai'], ['DANG_VE_SINH', 'BAO_TRI'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể chuyển lồng đang có thú sang trạng thái vệ sinh hoặc bảo trì.',
                ], 422);
            }

            $status = in_array($fields['trangthai'], ['DANG_VE_SINH', 'BAO_TRI'], true)
                ? $fields['trangthai']
                : ($actualCount > 0 ? 'DA_CO_THU' : 'CON_TRONG');

            DB::table('cages')->where('cage_id', $id)->update([
                'malong' => trim($fields['malong']),
                'ten_long' => trim($fields['ten_long']),
                'loailong' => $fields['loailong'],
                'succhua' => $fields['succhua'],
                'soluonghientai' => $actualCount,
                'trangthai' => $status,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật lồng lưu trú thành công!',
                'data' => DB::table('cages')->where('cage_id', $id)->first(),
            ]);
        });
    }

    public function destroy(int $id)
    {
        return DB::transaction(function () use ($id) {
            $cage = DB::table('cages')->where('cage_id', $id)->lockForUpdate()->first();
            if (!$cage) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy lồng.'], 404);
            }

            $hasPets = DB::table('pets')->where('cage_id', $id)->exists();
            $hasHistory = DB::table('pet_boardings')->where('cage_id', $id)->exists();

            if ($hasPets || $hasHistory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa lồng đã có thú cưng hoặc lịch sử lưu trú. Bạn có thể đổi tên hoặc chuyển trạng thái lồng.',
                ], 409);
            }

            DB::table('cages')->where('cage_id', $id)->delete();

            return response()->json(['status' => 'success', 'message' => 'Đã xóa lồng lưu trú.']);
        });
    }
}
