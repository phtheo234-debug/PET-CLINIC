<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MedicalController extends Controller
{
    public function __construct(
        protected InventoryController $inventory,
        private CageOccupancyService $cageOccupancy
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
                ->leftJoin('cages as current_cages', 'pets.cage_id', '=', 'current_cages.cage_id')
                ->leftJoin('boarding_requests as br', 'br.medical_record_id', '=', 'medical_records.record_id')
                ->leftJoin('cages as suggested_cages', 'br.suggested_cage_id', '=', 'suggested_cages.cage_id')
                ->select(
                    'medical_records.*',
                    'pets.mathucung',
                    'pets.ten_thucung',
                    'pets.loai',
                    'pets.giong',
                    DB::raw('COALESCE(current_cages.malong, suggested_cages.malong) as malong'),
                    DB::raw('COALESCE(current_cages.ten_long, suggested_cages.ten_long) as ten_long'),
                    'br.status as boarding_status',
                    'br.loai_long_yeu_cau',
                    'br.yeu_cau_cach_ly'
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
     * FR-MED-01: Tạo phiếu khám và tạo yêu cầu lưu trú khi bác sĩ chỉ định nhập viện.
     * Việc chọn lồng chỉ diễn ra sau khi nhân viên xác nhận đề xuất của hệ thống.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'cannangkhi_kham' => 'required|numeric|min:0.1',
            'trieuchung' => 'nullable|string',
            'chandoan' => 'required|string',
            'trangthai' => 'required|in:DANG_DIEU_TRI,HOAN_THANH,CHUYEN_VIEN',
            'huong_xuly' => 'nullable|in:OUTPATIENT,INPATIENT,SURGERY,TRANSFER',
            'yeu_cau_cach_ly' => 'nullable|boolean',
        ]);

        try {
            return DB::transaction(function () use ($request, $fields) {
                $pet = DB::table('pets')
                    ->where('pet_id', $fields['pet_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$pet) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy thú cưng.',
                    ], 404);
                }

                $huongXuLy = $fields['huong_xuly'] ?? 'OUTPATIENT';
                $needsIsolation = (bool) ($fields['yeu_cau_cach_ly'] ?? false);

                $recordId = DB::table('medical_records')->insertGetId([
                    'pet_id' => $fields['pet_id'],
                    'doctor_id' => $request->user()->user_id,
                    'ngaykham' => now(),
                    'trieuchung' => $fields['trieuchung'] ?? null,
                    'chandoan' => $fields['chandoan'],
                    'cannangkhi_kham' => $fields['cannangkhi_kham'],
                    'trangthai' => $fields['trangthai'],
                    'huong_xuly' => $huongXuLy,
                    'yeu_cau_cach_ly' => $needsIsolation,
                ]);

                DB::table('weight_logs')->insert([
                    'pet_id' => $fields['pet_id'],
                    'cannang' => $fields['cannangkhi_kham'],
                    'ngaydo' => now(),
                    'nguoido' => $request->user()->user_id,
                    'ghichu' => 'Ghi nhận từ phiếu khám bệnh #' . $recordId,
                ]);

                $boardingRequest = null;
                $alreadyBoarded = false;

                if (in_array($huongXuLy, ['INPATIENT', 'SURGERY'], true)) {
                    if ($pet->cage_id) {
                        $alreadyBoarded = true;
                        $boardingRequest = DB::table('boarding_requests')
                            ->where('pet_id', $pet->pet_id)
                            ->where('status', 'checked_in')
                            ->orderByDesc('request_id')
                            ->first();
                    } else {
                        $requiredCageType = $needsIsolation
                            ? 'CACH_LY'
                            : ($huongXuLy === 'SURGERY' ? 'CAP_CUU_ICU' : 'THUONG');

                        $pendingRequest = DB::table('boarding_requests')
                            ->where('pet_id', $pet->pet_id)
                            ->where('status', 'pending')
                            ->lockForUpdate()
                            ->first();

                        $requestData = [
                            'medical_record_id' => $recordId,
                            'loai_long_yeu_cau' => $requiredCageType,
                            'yeu_cau_cach_ly' => $needsIsolation,
                            'ghi_chu' => $needsIsolation
                                ? 'Bác sĩ chỉ định cách ly.'
                                : ($huongXuLy === 'SURGERY' ? 'Lưu trú theo chỉ định phẫu thuật.' : 'Lưu trú điều trị nội trú.'),
                            'updated_at' => now(),
                        ];

                        if ($pendingRequest) {
                            DB::table('boarding_requests')
                                ->where('request_id', $pendingRequest->request_id)
                                ->update($requestData);
                            $requestId = $pendingRequest->request_id;
                        } else {
                            $requestId = DB::table('boarding_requests')->insertGetId(array_merge(
                                $requestData,
                                [
                                    'pet_id' => $pet->pet_id,
                                    'status' => 'pending',
                                    'created_at' => now(),
                                ]
                            ));
                        }

                        $boardingRequest = DB::table('boarding_requests')
                            ->where('request_id', $requestId)
                            ->first();
                    }
                }

                $isTransfer = $fields['trangthai'] === 'CHUYEN_VIEN' || $huongXuLy === 'TRANSFER';

                if ($isTransfer) {
                    if ($pet->cage_id) {
                        $oldCageId = (int) $pet->cage_id;

                        DB::table('pet_boardings')
                            ->where('pet_id', $pet->pet_id)
                            ->where('cage_id', $oldCageId)
                            ->where('status', 'checked_in')
                            ->whereNull('check_out')
                            ->update([
                                'check_out' => now(),
                                'status' => 'checked_out',
                                'updated_at' => now(),
                            ]);

                        $this->cageOccupancy->sync($oldCageId);
                    }

                    DB::table('boarding_requests')
                        ->where('pet_id', $pet->pet_id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled', 'updated_at' => now()]);

                    DB::table('boarding_requests')
                        ->where('pet_id', $pet->pet_id)
                        ->where('status', 'checked_in')
                        ->update(['status' => 'completed', 'updated_at' => now()]);

                    DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                        'trangthai' => 'CHUYEN_VIEN',
                        'cage_id' => null,
                    ]);
                }

                $message = 'Tạo phiếu khám bệnh thành công!';
                if ($boardingRequest && !$alreadyBoarded) {
                    $message .= ' Đã tạo yêu cầu lưu trú. Hệ thống sẽ đề xuất lồng để nhân viên xác nhận.';
                } elseif ($alreadyBoarded) {
                    $message .= ' Thú cưng đang có lồng lưu trú, không tạo lượt xếp lồng trùng.';
                } elseif ($isTransfer) {
                    $message .= ' Đã cập nhật trạng thái chuyển viện và giải phóng lồng (nếu có).';
                }

                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'boarding_request' => $boardingRequest,
                    'data' => DB::table('medical_records')->where('record_id', $recordId)->first(),
                ], 201);
            });
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi tạo phiếu khám: ' . $e->getMessage(),
            ], 500);
        }
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