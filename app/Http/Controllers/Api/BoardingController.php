<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cage;
use App\Models\Pet;
use App\Models\PetBoarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    public function assignCage(Request $request)
    {
        $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'cage_id' => 'required|exists:cages,id',
            'check_in' => 'required|date',
        ]);

        $pet = Pet::findOrFail($request->pet_id);
        $cage = Cage::findOrFail($request->cage_id);

        // Kiểm tra lồng có trống hay không
        if ($cage->status !== 'available') {
            return response()->json(['error' => 'Lồng này đang có thú cưng hoặc đang bảo trì!'], 422);
        }

        // Xử lý ràng buộc quy tắc sức chứa BR-03
        if ($pet->weight > $cage->max_weight) {
            return response()->json([
                'error' => "Vi phạm BR-03: Thú cưng nặng {$pet->weight}kg vượt quá tải trọng tối đa của lồng ({$cage->max_weight}kg)!"
            ], 422);
        }

        DB::beginTransaction();
        try {
            $boarding = PetBoarding::create([
                'pet_id' => $pet->id,
                'cage_id' => $cage->id,
                'check_in' => $request->check_in,
                'status' => 'checked_in'
            ]);

            // Cập nhật trạng thái lồng thành đang sử dụng
            $cage->update(['status' => 'occupied']);

            DB::commit();
            return response()->json(['message' => 'Xếp lồng thành công!', 'data' => $boarding], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}
