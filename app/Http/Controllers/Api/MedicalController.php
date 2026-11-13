<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use App\Services\CageWaitingListService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MedicalController extends Controller
{
    public function __construct(
        protected InventoryController $inventory,
        private CageOccupancyService $cageOccupancy,
        private CageWaitingListService $cageWaitingList
    ) {
    }

    /**
     * Lấy danh sách lịch sử khám bệnh gần đây
     */
    public function index()
    {
        try {
            $records = DB::table('medical_records')
                ->leftJoin('pets', 'medical_records.pet_id', '=', 'pets.pet_id')
                ->leftJoin('cages', 'pets.cage_id', '=', 'cages.cage_id')
                ->select(
                    'medical_records.*',
                    'pets.mathucung',
                    'pets.ten_thucung',
                    'pets.loai',
                    'pets.giong',
                    'cages.malong',
                    'cages.ten_long'
                )
                ->orderByDesc('medical_records.ngaykham')
                ->limit(20)
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $records
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi tải lịch sử khám: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FR-MED-01: Tạo phiếu khám bệnh & Tự động xếp lồng nếu Nhập viện / Phẫu thuật
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'pet_id'          => 'required|integer|exists:pets,pet_id',
            'cannangkhi_kham' => 'required|numeric|min:0.1',
            'trieuchung'      => 'nullable|string',
            'chandoan'        => 'required|string',
            'trangthai'       => 'required|in:DANG_DIEU_TRI,HOAN_THANH,CHUYEN_VIEN',
            'huong_xuly'      => 'nullable|in:OUTPATIENT,INPATIENT,SURGERY,TRANSFER',
            'isolation_required' => 'nullable|boolean',
        ]);

        try {
            return DB::transaction(function () use ($request, $fields) {
                $pet = DB::table('pets')
                    ->where('pet_id', $fields['pet_id'])
                    ->lockForUpdate()
                    ->first();

                // 1. Tạo phiếu khám trong bảng medical_records
                $recordId = DB::table('medical_records')->insertGetId([
                    'pet_id'          => $fields['pet_id'],
                    'doctor_id'       => $request->user()->user_id ?? 1,
                    'ngaykham'        => now(),
                    'trieuchung'      => $fields['trieuchung'] ?? null,
                    'chandoan'        => $fields['chandoan'],
                    'cannangkhi_kham' => $fields['cannangkhi_kham'],
                    'trangthai'       => $fields['trangthai'],
                    'huong_xuly'      => $fields['huong_xuly'] ?? null,
                    'isolation_required' => (bool) ($fields['isolation_required'] ?? false),
                ]);

                // 2. Tự động lưu lịch sử cân nặng vào weight_logs (BR-04)
                DB::table('weight_logs')->insert([
                    'pet_id'  => $fields['pet_id'],
                    'cannang' => $fields['cannangkhi_kham'],
                    'ngaydo'  => now(),
                    'nguoido' => $request->user()->user_id ?? 1,
                    'ghichu'  => 'Ghi nhận từ phiếu khám bệnh #' . $recordId,
                ]);

                $assignedCage = null;
                $huongXuLy = $fields['huong_xuly'] ?? null;

                // 3. Nhập viện/phẫu thuật: xếp tự động theo loài, cân nặng và yêu cầu cách ly.
                $isolationRequired = (bool) ($fields['isolation_required'] ?? false);
                $waitingForCage = false;

                if (in_array($huongXuLy, ['INPATIENT', 'SURGERY'], true)) {
                    $assignment = $this->cageWaitingList->assignOrQueue(
                        (int) $pet->pet_id,
                        (float) $fields['cannangkhi_kham'],
                        $huongXuLy,
                        $isolationRequired
                    );
                    $assignedCage = $assignment['cage'];
                    $waitingForCage = $assignment['waiting'];
                }

                // 4. Nếu chẩn đoán kết luận CHUYEN_VIEN -> Tự động giải phóng lồng lưu trú
                if ($fields['trangthai'] === 'CHUYEN_VIEN' || $huongXuLy === 'TRANSFER') {
                    if ($pet->cage_id) {
                        $oldCageId = (int) $pet->cage_id;
                        $activeBoarding = DB::table('pet_boardings')
                            ->where('pet_id', $pet->pet_id)
                            ->where('cage_id', $oldCageId)
                            ->where('status', 'checked_in')
                            ->whereNull('check_out')
                            ->lockForUpdate()
                            ->first();

                        if ($activeBoarding) {
                            DB::table('pet_boardings')
                                ->where('id', $activeBoarding->id)
                                ->update([
                                    'check_out' => now(),
                                    'status' => 'checked_out',
                                    'updated_at' => now(),
                                ]);
                        }

                        DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                            'trangthai' => 'CHUYEN_VIEN',
                            'cage_id' => null,
                        ]);

                        $this->cageOccupancy->sync($oldCageId);
                    }

                    DB::table('pets')->where('pet_id', $fields['pet_id'])->update([
                        'trangthai' => 'CHUYEN_VIEN',
                        'cage_id'   => null
                    ]);
                }

                $msg = 'Tạo phiếu khám bệnh thành công!';
                if ($assignedCage) {
                    $msg .= " Đã tự động xếp vào lồng {$assignedCage->malong} ({$assignedCage->ten_long}).";
                } elseif (in_array($huongXuLy, ['INPATIENT', 'SURGERY'], true) && $waitingForCage) {
                    $msg .= ' Hiện chưa có lồng phù hợp; thú cưng đã được đưa vào danh sách chờ và quản trị viên sẽ được thông báo trên trang lồng.';
                }

                return response()->json([
                    'status'        => 'success',
                    'message'       => $msg,
                    'assigned_cage' => $assignedCage,
                    'data'          => DB::table('medical_records')->where('record_id', $recordId)->first()
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi tạo phiếu khám: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tự động chọn lồng trống phù hợp theo: loại lồng, sức chứa (BR-03), cân nặng, loài/giống & giới tính
     */
    private function autoAssignSuitableCage(object $pet, float $weight, string $huongXuLy): ?object
    {
        $hasMaxWeightCol = Schema::hasColumn('cages', 'tai_trong_toi_da');

        // Ưu tiên lồng ICU/Hậu phẫu nếu chỉ định phẫu thuật, lồng thường nếu nhập viện nội trú
        $preferredTypes = ($huongXuLy === 'SURGERY')
            ? ['CAP_CUU_ICU', 'THUONG']
            : ['THUONG', 'CAP_CUU_ICU'];

        $cages = DB::table('cages')
            ->whereNotIn('trangthai', ['DANG_VE_SINH', 'BAO_TRI'])
            ->when($hasMaxWeightCol, function ($q) use ($weight) {
                $q->where(function ($sub) use ($weight) {
                    $sub->whereNull('tai_trong_toi_da')
                        ->orWhere('tai_trong_toi_da', '>=', $weight);
                });
            })
            ->lockForUpdate()
            ->get();

        // Đếm thú thực tế để không bị ảnh hưởng bởi số lượng cũ đang lệch.
        foreach ($cages as $cage) {
            $cage->soluonghientai = DB::table('pets')
                ->where('cage_id', $cage->cage_id)
                ->count();
        }

        // Lọc lồng phù hợp với đặc điểm loài/giống, giới tính, cân nặng và sức chứa.
        $suitable = $cages->filter(function ($cage) use ($pet, $weight, $hasMaxWeightCol) {
            if ((int) $cage->soluonghientai >= (int) $cage->succhua) {
                return false;
            }
            // Nếu bảng cages chưa có cột tai_trong_toi_da, kiểm tra theo tên/kích thước lồng
            if (!$hasMaxWeightCol) {
                $cageLabel = mb_strtolower(($cage->ten_long ?? '') . ' ' . ($cage->malong ?? ''));
                if (str_contains($cageLabel, 'nhỏ') && $weight > 10) {
                    return false;
                }
                if ((str_contains($cageLabel, 'vừa') || str_contains($cageLabel, 'trung')) && $weight > 25) {
                    return false;
                }
            }

            // Nếu lồng trống hoàn toàn -> đạt điều kiện
            if ((int) $cage->soluonghientai === 0) {
                return true;
            }

            // Nếu lồng có sức chứa > 1 và đang có thú ở chung -> phải cùng loài, cùng giống (nếu có) và cùng giới tính
            $occupants = DB::table('pets')->where('cage_id', $cage->cage_id)->get();
            foreach ($occupants as $other) {
                if (mb_strtolower($other->loai) !== mb_strtolower($pet->loai)) {
                    return false;
                }
                if ($pet->giong && $other->giong && mb_strtolower($other->giong) !== mb_strtolower($pet->giong)) {
                    return false;
                }
                if ($pet->gioitinh !== 'CHUA_XAC_DINH' && $other->gioitinh !== 'CHUA_XAC_DINH' && $other->gioitinh !== $pet->gioitinh) {
                    return false;
                }
            }

            return true;
        });

        // Sắp xếp ưu tiên: đúng loại lồng (ICU/Thường) -> lồng trống hoàn toàn trước -> lồng phù hợp cân nặng nhất
        $selected = $suitable->sortBy(function ($cage) use ($preferredTypes) {
            $typePriority = array_search($cage->loailong, $preferredTypes, true);
            $typePriority = ($typePriority === false) ? 99 : $typePriority;
            $emptyPriority = ((int) $cage->soluonghientai === 0) ? 0 : 1;
            return [$typePriority, $emptyPriority, $cage->cage_id];
        })->first();

        if (!$selected) {
            return null;
        }

        // Ghi nhận xếp lồng vào pet_boardings, pets và cages (giống BoardingController::assignCage)
        DB::table('pet_boardings')->insert([
            'pet_id'     => $pet->pet_id,
            'cage_id'    => $selected->cage_id,
            'check_in'   => now(),
            'status'     => 'checked_in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->where('pet_id', $pet->pet_id)->update([
            'cage_id'   => $selected->cage_id,
            'trangthai' => 'NOI_TRU',
        ]);

        return $this->cageOccupancy->sync((int) $selected->cage_id);
    }

    /**
     * FR-MED-02: Kê đơn tính theo mg/kg và tự động xuất kho FEFO
     */
    public function createPrescription(Request $request)
    {
        $request->validate([
            'pet_id'            => 'required|integer|exists:pets,pet_id',
            'medical_record_id' => 'required|integer|exists:medical_records,record_id',
            'medicine_id'       => 'required|integer|exists:inventory,item_id',
            'doses_per_day'     => 'required|integer|min:1',
            'total_days'        => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $record = DB::table('medical_records')->where('record_id', $request->medical_record_id)->first();
                if ((int) $record->pet_id !== (int) $request->pet_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Hồ sơ khám này không thuộc thú cưng đã chọn!'
                    ], 422);
                }

                $item = DB::table('inventory')->where('item_id', $request->medicine_id)->first();
                
                // Chỉ cho phép kê đơn từ mặt hàng thuốc
                if ($item->danhmuc !== 'THUOC') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Chỉ được kê đơn với mặt hàng thuộc danh mục thuốc!'
                    ], 422);
                }

                // Không tự lấy liều mặc định nếu thuốc chưa được cấu hình
                if (
                    $item->lieu_mg_kg === null ||
                    $item->mg_moi_don_vi === null ||
                    (float) $item->lieu_mg_kg <= 0 ||
                    (float) $item->mg_moi_don_vi <= 0
                ) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thuốc chưa được cấu hình liều mg/kg hoặc hàm lượng mg mỗi đơn vị. Vui lòng cập nhật kho trước khi kê đơn!'
                    ], 422);
                }

                $weight = (float) $record->cannangkhi_kham;
                $mgPerKg = (float) $item->lieu_mg_kg;
                $mgPerUnit = (float) $item->mg_moi_don_vi;


                $mgPerDose = $weight * $mgPerKg;
                $unitsPerDose = $mgPerDose / $mgPerUnit;
                $totalUnits = (int) ceil(round($unitsPerDose * $request->doses_per_day * $request->total_days, 6));

                $deductions = $this->inventory->fefoDeduct((int) $item->item_id, $totalUnits);

                $prescriptionId = DB::table('prescriptions')->insertGetId([
                    'record_id' => $record->record_id,
                    'item_id'   => $item->item_id,
                    'soluong'   => $totalUnits,
                    'huongdan'  => "{$request->doses_per_day} lần/ngày, mỗi lần " . round($unitsPerDose, 2)
                        . " {$item->donvitinh} (liều " . round($mgPerDose, 1) . "mg), dùng {$request->total_days} ngày",
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Kê đơn thuốc thành công!',
                    'data'    => [
                        'prescription_id' => $prescriptionId,
                        'dose_mg'         => round($mgPerDose, 1),
                        'total_units'     => $totalUnits,
                        'fefo_deductions' => $deductions,
                    ],
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kê đơn thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Tra cứu lịch sử khám bệnh & đơn thuốc của thú cưng
     */
    public function getHistoryByPet($petId)
    {
        $records = DB::table('medical_records')
            ->where('pet_id', $petId)
            ->orderByDesc('ngaykham')
            ->get();

        foreach ($records as $record) {
            $record->prescriptions = DB::table('prescriptions')
                ->join('inventory', 'prescriptions.item_id', '=', 'inventory.item_id')
                ->where('prescriptions.record_id', $record->record_id)
                ->select('prescriptions.*', 'inventory.ten_sanpham', 'inventory.donvitinh')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $records
        ]);
    }
}