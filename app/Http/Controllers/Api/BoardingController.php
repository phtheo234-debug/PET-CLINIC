<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use App\Services\CageWaitingListService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    public function __construct(
        private CageOccupancyService $cageOccupancy,
        private CageWaitingListService $cageWaitingList
    )
    {
    }

    /**
     * Danh sách lồng và thú cưng đang thực tế ở trong từng lồng.
     */
    public function index()
    {
        $cages = DB::table('cages')->orderBy('cage_id')->get();

        foreach ($cages as $cage) {
            // Đồng bộ dữ liệu cũ bị lệch ngay khi tải danh sách lồng.
            $syncedCage = DB::transaction(function () use ($cage) {
                return $this->cageOccupancy->sync((int) $cage->cage_id);
            });

            $cage->soluonghientai = $syncedCage
                ? (int) $syncedCage->soluonghientai
                : 0;
            $cage->trangthai = $syncedCage->trangthai ?? $cage->trangthai;
            $cage->pets = DB::table('pets')
                ->select('pet_id', 'mathucung', 'ten_thucung', 'loai', 'giong', 'trangthai')
                ->where('cage_id', $cage->cage_id)
                ->get();

            $cage->da_day = $cage->soluonghientai >= (int) $cage->succhua;
        }

        return response()->json([
            'status' => 'success',
            'data' => $cages,
        ]);
    }

    /**
     * Danh sách thú cưng đang chờ lồng phù hợp.
     */
    public function waitingList()
    {
        $rows = DB::table('pet_cage_waitlist')
            ->join('pets', 'pet_cage_waitlist.pet_id', '=', 'pets.pet_id')
            ->where('pet_cage_waitlist.status', 'WAITING')
            ->orderBy('pet_cage_waitlist.created_at')
            ->select(
                'pet_cage_waitlist.waitlist_id',
                'pet_cage_waitlist.weight',
                'pet_cage_waitlist.treatment_type',
                'pet_cage_waitlist.isolation_required',
                'pet_cage_waitlist.created_at',
                'pets.pet_id',
                'pets.mathucung',
                'pets.ten_thucung',
                'pets.loai',
                'pets.giong'
            )
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
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
            'isolation_required' => 'nullable|boolean',
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

                $isolationRequired = (bool) ($fields['isolation_required'] ?? false);
                if (($cage->loailong === 'CACH_LY') !== $isolationRequired) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Thú cưng cách ly chỉ được xếp vào lồng cách ly; thú cưng bình thường không được xếp vào lồng cách ly.',
                    ], 422);
                }

                $effectiveCapacity = $cage->loailong === 'CACH_LY' ? 1 : (int) $cage->succhua;
                if ($actualCount >= $effectiveCapacity) {
                    $this->cageOccupancy->sync((int) $cage->cage_id);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Lồng đã đạt sức chứa tối đa.',
                    ], 422);
                }

                if ($actualCount > 0) {
                    $occupants = DB::table('pets')->where('cage_id', $cage->cage_id)->get();
                    foreach ($occupants as $occupant) {
                        $normalize = fn ($value) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim((string) $value)));
                        if ($normalize($occupant->loai) !== $normalize($pet->loai)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Không thể xếp khác loài vào chung một lồng.',
                            ], 422);
                        }
                        if ($occupant->gioitinh !== 'CHUA_XAC_DINH' && $pet->gioitinh !== 'CHUA_XAC_DINH'
                            && $occupant->gioitinh !== $pet->gioitinh) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Không thể xếp thú cưng khác giới tính vào chung một lồng.',
                            ], 422);
                        }
                        $occupantIsolation = DB::table('pet_boardings')
                            ->where('pet_id', $occupant->pet_id)
                            ->where('cage_id', $cage->cage_id)
                            ->where('status', 'checked_in')
                            ->whereNull('check_out')
                            ->value('isolation_required');
                        if ((bool) $occupantIsolation) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Lồng đang có thú cưng cách ly, không thể xếp thêm thú khác.',
                            ], 422);
                        }
                    }
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('cages', 'loai_phu_hop')
                    && $cage->loai_phu_hop !== null
                    && $cage->loai_phu_hop !== 'TAT_CA') {
                    $normalize = fn ($value) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim((string) $value)));
                    if ($normalize($cage->loai_phu_hop) !== $normalize($pet->loai)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Loài thú cưng không phù hợp với loại lồng đã chọn.',
                        ], 422);
                    }
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
                    'isolation_required' => $isolationRequired,
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

                $this->cageOccupancy->sync((int) $cage->cage_id);
                $assignedFromWaiting = $this->cageWaitingList->dispatchWaiting();
                $updatedCage = $this->cageOccupancy->sync((int) $cage->cage_id);

                return response()->json([
                    'status' => 'success',
                    'message' => $assignedFromWaiting > 0
                        ? "Trả lồng thành công! Hệ thống đã tự động xếp {$assignedFromWaiting} thú cưng trong danh sách chờ."
                        : 'Trả lồng thành công!',
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
