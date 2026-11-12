<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    public function __construct(private CageOccupancyService $cageOccupancy)
    {
    }

    /**
     * Danh sách lồng và thú cưng đang thực tế ở trong từng lồng.
     */
    public function index()
    {
        $cages = DB::table('cages')->orderBy('cage_id')->get();

        foreach ($cages as $cage) {
            $cage->pets = DB::table('pets')
                ->select('pet_id', 'mathucung', 'ten_thucung', 'loai', 'giong', 'trangthai')
                ->where('cage_id', $cage->cage_id)
                ->get();

            // Số thú hiển thị được lấy từ danh sách thú thực tế.
            $cage->soluonghientai = $cage->pets->count();
            $cage->da_day = $cage->soluonghientai >= (int) $cage->succhua;
        }

        return response()->json([
            'status' => 'success',
            'data' => $cages,
        ]);
    }

    /**
     * Xếp thú cưng vào lồng.
     */
    public function assignCage(Request $request)
    {
        $fields = $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'cage_id' => 'required|integer|exists:cages,cage_id',
            'check_in' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($fields) {
                $pet = DB::table('pets')
                    ->where('pet_id', $fields['pet_id'])
                    ->lockForUpdate()
                    ->first();

                if ($pet->cage_id !== null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Thú cưng đang ở trong một lồng khác. Hãy trả lồng trước khi xếp lồng mới.',
                    ], 422);
                }

                $cage = DB::table('cages')
                    ->where('cage_id', $fields['cage_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$cage) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy lồng lưu trú.',
                    ], 404);
                }

                if (in_array($cage->trangthai, ['DANG_VE_SINH', 'BAO_TRI'], true)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Lồng đang vệ sinh hoặc bảo trì, không thể xếp thú.',
                    ], 422);
                }

                // Đếm từ pets thay vì tin vào số lượng đã lưu có thể bị lệch.
                $actualCount = DB::table('pets')
                    ->where('cage_id', $cage->cage_id)
                    ->count();

                if ($actualCount >= (int) $cage->succhua) {
                    $this->cageOccupancy->sync((int) $cage->cage_id);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Lồng đã đạt sức chứa tối đa.',
                    ], 422);
                }

                $latestWeightLog = DB::table('weight_logs')
                    ->where('pet_id', $pet->pet_id)
                    ->orderByDesc('ngaydo')
                    ->first();

                $currentWeight = $latestWeightLog ? (float) $latestWeightLog->cannang : null;

                if (
                    $currentWeight !== null
                    && isset($cage->tai_trong_toi_da)
                    && $cage->tai_trong_toi_da !== null
                    && $currentWeight > (float) $cage->tai_trong_toi_da
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Thú cưng nặng {$currentWeight}kg vượt tải trọng tối đa của lồng ({$cage->tai_trong_toi_da}kg).",
                    ], 422);
                }

                $activeBoarding = DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->whereNull('check_out')
                    ->where('status', 'checked_in')
                    ->exists();

                if ($activeBoarding) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Thú cưng đã có lượt lưu trú đang hoạt động.',
                    ], 422);
                }

                DB::table('pet_boardings')->insert([
                    'pet_id' => $pet->pet_id,
                    'cage_id' => $cage->cage_id,
                    'check_in' => $fields['check_in'],
                    'status' => 'checked_in',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id' => $cage->cage_id,
                    'trangthai' => 'NOI_TRU',
                ]);

                $updatedCage = $this->cageOccupancy->sync((int) $cage->cage_id);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Xếp lồng lưu trú thành công!',
                    'data' => [
                        'pet_id' => $pet->pet_id,
                        'cage' => $updatedCage,
                    ],
                ], 201);
            });
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Không thể xếp lồng do lỗi hệ thống.',
            ], 500);
        }
    }

    /**
     * Trả lồng / kết thúc lượt lưu trú.
     */
    public function releaseCage(Request $request)
    {
        $fields = $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'check_out' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($fields) {
                $pet = DB::table('pets')
                    ->where('pet_id', $fields['pet_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$pet || !$pet->cage_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Thú cưng hiện không ở trong lồng nào.',
                    ], 422);
                }

                $cage = DB::table('cages')
                    ->where('cage_id', $pet->cage_id)
                    ->lockForUpdate()
                    ->first();

                $activeBoarding = DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->where('cage_id', $pet->cage_id)
                    ->where('status', 'checked_in')
                    ->whereNull('check_out')
                    ->lockForUpdate()
                    ->first();

                if (!$activeBoarding) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy lượt lưu trú đang hoạt động. Vui lòng kiểm tra lịch sử lưu trú.',
                    ], 422);
                }

                DB::table('pet_boardings')
                    ->where('id', $activeBoarding->id)
                    ->update([
                        'check_out' => $fields['check_out'],
                        'status' => 'checked_out',
                        'updated_at' => now(),
                    ]);

                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id' => null,
                    'trangthai' => 'NGOAI_TRU',
                ]);

                $updatedCage = $this->cageOccupancy->sync((int) $cage->cage_id);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Trả lồng thành công!',
                    'data' => [
                        'cage' => $updatedCage,
                    ],
                ]);
            });
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Không thể trả lồng do lỗi hệ thống.',
            ], 500);
        }
    }
}
