<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpaUsageController extends Controller
{
    /**
     * Danh sách lịch sử dịch vụ Spa đã ghi nhận.
     */
    public function index(Request $request)
    {
        $query = DB::table('spa_usages as su')
            ->join('pets as p', 'p.pet_id', '=', 'su.pet_id')
            ->join('users as u', 'u.user_id', '=', 'su.staff_id')
            ->select(
                'su.*',
                'p.mathucung',
                'p.ten_thucung',
                'u.ho_ten as ten_nhanvien'
            )
            ->orderByDesc('su.created_at');

        if ($request->filled('pet_id')) {
            $query->where('su.pet_id', (int) $request->pet_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->limit(200)->get(),
        ]);
    }

    /**
     * Nhân viên ghi nhận dịch vụ Spa đã thực hiện cho thú cưng.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->vaitro, ['NHAN_VIEN', 'QUAN_TRI'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ nhân viên hoặc quản trị viên được ghi nhận dịch vụ Spa.',
            ], 403);
        }

        $fields = $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'item_id' => 'required|integer|exists:inventory,item_id',
            'soluong' => 'required|integer|min:1|max:100',
            'ghichu' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($fields, $user) {
            $service = DB::table('inventory')
                ->where('item_id', $fields['item_id'])
                ->lockForUpdate()
                ->first();

            if (!$service || $service->danhmuc !== 'DICH_VU_SPA') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dịch vụ được chọn không thuộc danh mục Spa.',
                ], 422);
            }

            $pet = DB::table('pets')->where('pet_id', $fields['pet_id'])->first();
            if (!$pet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy thú cưng.',
                ], 404);
            }

            if ($pet->trangthai !== 'NOI_TRU') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Chỉ ghi nhận dịch vụ Spa cho thú cưng đang nội trú để tính vào hóa đơn xuất viện.',
                ], 422);
            }

            $unitPrice = (float) $service->dongia;
            $quantity = (int) $fields['soluong'];
            $total = $unitPrice * $quantity;

            $usageId = DB::table('spa_usages')->insertGetId([
                'pet_id' => $pet->pet_id,
                'item_id' => $service->item_id,
                'staff_id' => $user->user_id,
                'soluong' => $quantity,
                'ten_dichvu' => $service->ten_sanpham,
                'donvitinh' => $service->donvitinh,
                'dongia' => $unitPrice,
                'thanhtien' => $total,
                'ghichu' => $fields['ghichu'] ?? null,
                'invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã ghi nhận dịch vụ Spa. Phí sẽ được cộng vào hóa đơn khi thanh toán.',
                'data' => DB::table('spa_usages')->where('spa_usage_id', $usageId)->first(),
            ], 201);
        });
    }
}
