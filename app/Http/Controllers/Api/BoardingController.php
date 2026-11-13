<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
     * Danh sách yêu cầu lưu trú đang chờ nhân viên xác nhận.
     * Hệ thống chỉ đề xuất, chưa giữ chỗ hoặc cập nhật lồng ở bước này.
     */
    public function requests()
    {
        $requests = DB::table('boarding_requests as br')
            ->join('pets as p', 'p.pet_id', '=', 'br.pet_id')
            ->join('medical_records as mr', 'mr.record_id', '=', 'br.medical_record_id')
            ->where('br.status', 'pending')
            ->select(
                'br.request_id',
                'br.pet_id',
                'br.medical_record_id',
                'br.loai_long_yeu_cau',
                'br.yeu_cau_cach_ly',
                'br.ghi_chu',
                'br.created_at',
                'p.mathucung',
                'p.ten_thucung',
                'p.loai',
                'p.giong',
                'p.gioitinh',
                'mr.chandoan',
                'mr.trieuchung',
                'mr.cannangkhi_kham'
            )
            ->orderBy('br.created_at')
            ->get();

        foreach ($requests as $boardingRequest) {
            $boardingRequest->recommended_cage = $this->findSuitableCage($boardingRequest, false);
        }

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Nhân viên xác nhận đề xuất. Backend tự tìm lại lồng trong transaction,
     * không nhận cage_id từ trình duyệt để tránh chọn nhầm hoặc xếp trùng.
     */
    public function confirmRequest(Request $request, int $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $boardingRequest = DB::table('boarding_requests')
                    ->where('request_id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$boardingRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy yêu cầu lưu trú.',
                    ], 404);
                }

                if ($boardingRequest->status !== 'pending') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Yêu cầu này đã được xử lý hoặc không còn chờ xác nhận.',
                    ], 409);
                }

                $pet = DB::table('pets')
                    ->where('pet_id', $boardingRequest->pet_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pet) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy hồ sơ thú cưng.',
                    ], 404);
                }

                $oldCageId = $pet->cage_id ? (int) $pet->cage_id : null;
                $oldCage = $oldCageId ? DB::table('cages')->where('cage_id', $oldCageId)->first() : null;
                $latestPetWeight = DB::table('weight_logs')->where('pet_id', $pet->pet_id)->orderByDesc('ngaydo')->value('cannang');
                $examPetWeight = DB::table('medical_records')->where('record_id', $boardingRequest->medical_record_id)->value('cannangkhi_kham');
                $petWeight = (float) ($latestPetWeight ?? $examPetWeight ?? 0);
                $oldCageCompatible = $oldCage
                    && $oldCage->loailong === $boardingRequest->loai_long_yeu_cau
                    && mb_strtolower((string) ($oldCage->loai_ap_dung ?? '')) === mb_strtolower((string) $pet->loai)
                    && $petWeight > 0
                    && (float) ($oldCage->tai_trong_toi_da ?? 0) >= $petWeight;
                if ($oldCageCompatible) {
                    return response()->json(['status' => 'error', 'message' => 'Thú cưng đã ở đúng lồng phù hợp với chỉ định, loài và cân nặng.'], 409);
                }

                $activeBoarding = DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->where('status', 'checked_in')
                    ->whereNull('check_out')
                    ->lockForUpdate()
                    ->first();

                if ($activeBoarding && !$oldCageId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Thú cưng đã có lượt lưu trú đang hoạt động nhưng chưa đồng bộ lồng hiện tại.',
                    ], 409);
                }

                // Xác nhận đề xuất tự động hoặc lồng do nhân viên/quản trị tự chọn.
                // Backend luôn kiểm tra lại toàn bộ điều kiện trong transaction.
                $manualCageId = $request->input('cage_id');
                $cage = $this->findSuitableCage(
                    $boardingRequest,
                    true,
                    $oldCageId,
                    $manualCageId ? (int) $manualCageId : null
                );

                if (!$cage) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $manualCageId
                            ? 'Lồng bạn chọn không phù hợp, đã hết chỗ hoặc không còn khả dụng. Yêu cầu vẫn được giữ ở trạng thái chờ.'
                            : 'Hiện không còn lồng phù hợp. Yêu cầu vẫn được giữ ở trạng thái chờ.',
                    ], 422);
                }

                if ($oldCageId && $activeBoarding) {
                    DB::table('pet_boardings')->where('id', $activeBoarding->id)->update([
                        'check_out' => now(),
                        'status' => 'checked_out',
                        'updated_at' => now(),
                    ]);
                }

                DB::table('pet_boardings')->insert([
                    'pet_id' => $pet->pet_id,
                    'cage_id' => $cage->cage_id,
                    'check_in' => now(),
                    'status' => 'checked_in',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id' => $cage->cage_id,
                    'trangthai' => 'NOI_TRU',
                ]);

                DB::table('boarding_requests')
                    ->where('request_id', $id)
                    ->update([
                        'status' => 'checked_in',
                        'suggested_cage_id' => $cage->cage_id,
                        'confirmed_by' => $request->user()->user_id,
                        'confirmed_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($oldCageId) {
                    $this->cageOccupancy->sync($oldCageId);
                }
                $updatedCage = $this->cageOccupancy->sync((int) $cage->cage_id);

                return response()->json([
                    'status' => 'success',
                    'message' => ($oldCageId ? 'Đã chuyển thú cưng sang lồng ' : 'Đã xác nhận đề xuất và xếp thú cưng vào lồng ') . $updatedCage->malong . '.',
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
                'message' => 'Không thể xác nhận xếp lồng do lỗi hệ thống.',
            ], 500);
        }
    }

    /**
     * Tìm lồng theo loại được bác sĩ chỉ định, sức chứa, cân nặng,
     * yêu cầu cách ly và khả năng ở chung an toàn.
     */
    private function findSuitableCage(object $boardingRequest, bool $lock, ?int $excludeCageId = null, ?int $specificCageId = null): ?object
    {
        $pet = DB::table('pets')
            ->where('pet_id', $boardingRequest->pet_id)
            ->first();

        if (!$pet) {
            return null;
        }

        $latestWeight = DB::table('weight_logs')
            ->where('pet_id', $pet->pet_id)
            ->orderByDesc('ngaydo')
            ->first();

        $examWeight = $boardingRequest->cannangkhi_kham
            ?? DB::table('medical_records')->where('record_id', $boardingRequest->medical_record_id)->value('cannangkhi_kham');
        $weight = $latestWeight
            ? (float) $latestWeight->cannang
            : (float) ($examWeight ?? 0);

        $hasMaxWeightColumn = Schema::hasColumn('cages', 'tai_trong_toi_da');

        $query = DB::table('cages')
            ->where('loailong', $boardingRequest->loai_long_yeu_cau)
            ->whereNotIn('trangthai', ['DANG_VE_SINH', 'BAO_TRI']);
        if ($excludeCageId) {
            $query->where('cage_id', '!=', $excludeCageId);
        }
        if ($specificCageId) {
            $query->where('cage_id', $specificCageId);
        }

        if (Schema::hasColumn('cages', 'loai_ap_dung')) {
            $query->whereRaw('LOWER(loai_ap_dung) = ?', [mb_strtolower((string) $pet->loai)]);
        }

        if ($hasMaxWeightColumn && $weight > 0) {
            $query->where('tai_trong_toi_da', '>=', $weight);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        $cages = $query->orderBy('cage_id')->get();

        $selectedCage = null;
        $selectedOccupancy = PHP_INT_MAX;

        foreach ($cages as $cage) {
            $occupantsQuery = DB::table('pets')->where('cage_id', $cage->cage_id);

            if ($lock) {
                $occupantsQuery->lockForUpdate();
            }

            $occupants = $occupantsQuery->get();
            $actualCount = $occupants->count();

            if ($actualCount >= (int) $cage->succhua) {
                continue;
            }

            if ((bool) $boardingRequest->yeu_cau_cach_ly && $actualCount > 0) {
                continue;
            }

            if (!$hasMaxWeightColumn && $weight > 0) {
                $cageLabel = mb_strtolower(($cage->ten_long ?? '') . ' ' . ($cage->malong ?? ''));

                if (str_contains($cageLabel, 'nhỏ') && $weight > 10) {
                    continue;
                }

                if ((str_contains($cageLabel, 'vừa') || str_contains($cageLabel, 'trung')) && $weight > 25) {
                    continue;
                }
            }

            $compatible = true;

            foreach ($occupants as $otherPet) {
                if (mb_strtolower((string) $otherPet->loai) !== mb_strtolower((string) $pet->loai)) {
                    $compatible = false;
                    break;
                }

                if (
                    $pet->giong
                    && $otherPet->giong
                    && mb_strtolower($otherPet->giong) !== mb_strtolower($pet->giong)
                ) {
                    $compatible = false;
                    break;
                }

                if (
                    $pet->gioitinh !== 'CHUA_XAC_DINH'
                    && $otherPet->gioitinh !== 'CHUA_XAC_DINH'
                    && $otherPet->gioitinh !== $pet->gioitinh
                ) {
                    $compatible = false;
                    break;
                }
            }

            if (!$compatible) {
                continue;
            }

            $cage->soluonghientai = $actualCount;

            // Ưu tiên lồng trống hoàn toàn, sau đó mới đến lồng có ít thú hơn.
            if ($selectedCage === null || $actualCount < $selectedOccupancy) {
                $selectedCage = $cage;
                $selectedOccupancy = $actualCount;
            }
        }

        return $selectedCage;
    }
}
