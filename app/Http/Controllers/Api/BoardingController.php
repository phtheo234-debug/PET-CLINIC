<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    public function assignCage(Request $request)
    {
        $request->validate([
            'pet_id' => 'required|exists:pets,pet_id',
            'cage_id' => 'required|exists:cages,cage_id',
            'check_in' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $pet = DB::table('pets')->where('pet_id', $request->pet_id)->lockForUpdate()->first();
                $cage = DB::table('cages')->where('cage_id', $request->cage_id)->lockForUpdate()->first();

                if ($pet->cage_id) {
                    return response()->json(['error' => 'Thú cưng đang nằm ở một lồng khác. Hãy làm thủ tục xuất viện trước!'], 422);
                }

                // Lồng phải còn chỗ và không bảo trì / vệ sinh
                if (in_array($cage->trangthai, ['DANG_VE_SINH', 'BAO_TRI']) || $cage->soluonghientai >= $cage->succhua) {
                    return response()->json(['error' => 'Lồng này đã đầy hoặc đang bảo trì, vệ sinh!'], 422);
                }

                // Quy tắc BR-03: cân nặng không vượt tải trọng tối đa của lồng
                if ($cage->tai_trong_toi_da !== null && $pet->cannang !== null && $pet->cannang > $cage->tai_trong_toi_da) {
                    return response()->json([
                        'error' => "Vi phạm BR-03: Thú cưng nặng {$pet->cannang}kg vượt quá tải trọng tối đa của lồng ({$cage->tai_trong_toi_da}kg)!"
                    ], 422);
                }

                $id = DB::table('pet_boardings')->insertGetId([
                    'pet_id' => $pet->pet_id,
                    'cage_id' => $cage->cage_id,
                    'check_in' => $request->check_in,
                ]);

                DB::table('pets')->where('pet_id', $pet->pet_id)
                    ->update(['cage_id' => $cage->cage_id, 'trangthai' => 'NOI_TRU']);
                DB::table('cages')->where('cage_id', $cage->cage_id)
                    ->update(['soluonghientai' => DB::raw('soluonghientai + 1'), 'trangthai' => 'DA_CO_THU']);

                return response()->json([
                    'message' => 'Xếp lồng thành công!',
                    'data' => DB::table('pet_boardings')->where('boarding_id', $id)->first(),
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json(['error' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}
