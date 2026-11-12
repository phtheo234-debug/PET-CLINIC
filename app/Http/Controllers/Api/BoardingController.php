<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    /**
     * Lấy danh sách lồng lưu trú kèm thông tin thú cưng đang ở
     */
    public function index()
    {
        $cages = DB::table('cages')->get();

        foreach ($cages as $cage) {
            $cage->pets = DB::table('pets')
                ->select('pet_id', 'mathucung', 'ten_thucung', 'loai', 'giong', 'trangthai')
                ->where('cage_id', $cage->cage_id)
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $cages
        ]);
    }

    /**
     * FR-CAGE-02: Xếp lồng lưu trú cho thú cưng (BR-03)
     */
    public function assignCage(Request $request)
    {
        $request->validate([
            'pet_id'   => 'required|exists:pets,pet_id',
            'cage_id'  => 'required|exists:cages,cage_id',
            'check_in' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $pet = DB::table('pets')->where('pet_id', $request->pet_id)->lockForUpdate()->first();
                $cage = DB::table('cages')->where('cage_id', $request->cage_id)->lockForUpdate()->first();

                // Kiểm tra thú cưng đã có lồng chưa
                if ($pet->cage_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng đang nằm ở một lồng khác. Hãy làm thủ tục trả lồng trước!'
                    ], 422);
                }

                // Kiểm tra trạng thái lồng và sức chứa (BR-03)
                if (in_array($cage->trangthai, ['DANG_VE_SINH', 'BAO_TRI'])) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Lồng này đang trong quá trình vệ sinh hoặc bảo trì!'
                    ], 422);
                }

                if ($cage->soluonghientai >= $cage->succhua) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Vi phạm BR-03: Lồng này đã đạt sức chứa tối đa!'
                    ], 422);
                }

                // Lấy cân nặng mới nhất từ weight_logs để kiểm tra (nếu có trường tai_trong_toi_da)
                $latestWeightLog = DB::table('weight_logs')
                    ->where('pet_id', $pet->pet_id)
                    ->orderByDesc('ngaydo')
                    ->first();

                $currentWeight = $latestWeightLog ? $latestWeightLog->cannang : null;

                if (isset($cage->tai_trong_toi_da) && $cage->tai_trong_toi_da !== null && $currentWeight !== null) {
                    if ($currentWeight > $cage->tai_trong_toi_da) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => "Thú cưng nặng {$currentWeight}kg vượt quá tải trọng tối đa của lồng ({$cage->tai_trong_toi_da}kg)!"
                        ], 422);
                    }
                }

                // Tạo bản ghi lưu trú
                $boardingId = DB::table('pet_boardings')->insertGetId([
                    'pet_id'   => $pet->pet_id,
                    'cage_id'  => $cage->cage_id,
                    'check_in' => $request->check_in,
                ]);

                // Cập nhật trạng thái Thú cưng
                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id'   => $cage->cage_id,
                    'trangthai' => 'NOI_TRU'
                ]);

                // Cập nhật số lượng và trạng thái Lồng
                $newCount = $cage->soluonghientai + 1;
                DB::table('cages')->where('cage_id', $cage->cage_id)->update([
                    'soluonghientai' => $newCount,
                    'trangthai' => 'DA_CO_THU'
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Xếp lồng lưu trú thành công!',
                    'data'    => DB::table('pet_boardings')->where('id', $boardingId)->first() ?? DB::table('pet_boardings')->where('boarding_id', $boardingId)->first(),
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trả lồng / Xuất lồng lưu trú cho thú cưng
     */
    public function releaseCage(Request $request)
    {
        $request->validate([
            'pet_id'    => 'required|exists:pets,pet_id',
            'check_out' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $pet = DB::table('pets')->where('pet_id', $request->pet_id)->lockForUpdate()->first();

                if (!$pet->cage_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng hiện không ở trong lồng nào!'
                    ], 422);
                }

                $cage = DB::table('cages')->where('cage_id', $pet->cage_id)->lockForUpdate()->first();

                // Cập nhật ngày check_out trong pet_boardings
                DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->where('cage_id', $pet->cage_id)
                    ->whereNull('check_out')
                    ->update(['check_out' => $request->check_out]);

                // Giải phóng lồng cho thú cưng
                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id'   => null,
                    'trangthai' => 'NGOAI_TRU'
                ]);

                // Giảm số lượng hiện tại của lồng
                $newCount = max(0, $cage->soluonghientai - 1);

                // Cập nhật trạng thái theo số thú còn trong lồng
                $newStatus = $newCount > 0 ? 'DA_CO_THU' : 'CON_TRONG';

                DB::table('cages')->where('cage_id', $cage->cage_id)->update([
                    'soluonghientai' => $newCount,
                    'trangthai'      => $newStatus
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Trả lồng thành công! Lồng đã được cập nhật trạng thái.'
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }
}