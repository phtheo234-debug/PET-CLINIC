<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalController extends Controller
{
    public function __construct(protected InventoryController $inventory)
    {
    }

    /**
     * Kê đơn tính theo mg/kg và tự động xuất kho FEFO.
     * medicine_id = inventory.item_id, medical_record_id = medical_records.record_id
     */
    public function createPrescription(Request $request)
    {
        $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'medical_record_id' => 'required|integer|exists:medical_records,record_id',
            'medicine_id' => 'required|integer|exists:inventory,item_id',
            'doses_per_day' => 'required|integer|min:1',
            'total_days' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $record = DB::table('medical_records')->where('record_id', $request->medical_record_id)->first();
                if ((int) $record->pet_id !== (int) $request->pet_id) {
                    return response()->json(['status' => false, 'message' => 'Hồ sơ khám này không thuộc thú cưng đã chọn!'], 422);
                }

                $item = DB::table('inventory')->where('item_id', $request->medicine_id)->first();

                $weight = (float) $record->cannangkhi_kham;
                $mgPerKg = (float) ($item->lieu_mg_kg ?? 5);        // mặc định 5 mg/kg
                $mgPerUnit = (float) ($item->mg_moi_don_vi ?? 25);  // mặc định 25 mg/đơn vị

                $mgPerDose = $weight * $mgPerKg;
                $unitsPerDose = $mgPerDose / $mgPerUnit;
                $totalUnits = (int) ceil(round($unitsPerDose * $request->doses_per_day * $request->total_days, 6));

                // Trừ kho FEFO; thiếu hàng sẽ ném lỗi và hoàn tác cả đơn thuốc
                $deductions = $this->inventory->fefoDeduct((int) $item->item_id, $totalUnits);

                $prescriptionId = DB::table('prescriptions')->insertGetId([
                    'record_id' => $record->record_id,
                    'item_id' => $item->item_id,
                    'soluong' => $totalUnits,
                    'huongdan' => "{$request->doses_per_day} lần/ngày, mỗi lần " . round($unitsPerDose, 2)
                        . " {$item->donvitinh} (liều " . round($mgPerDose, 1) . "mg), dùng {$request->total_days} ngày",
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Kê đơn thuốc thành công!',
                    'data' => [
                        'prescription_id' => $prescriptionId,
                        'dose_mg' => round($mgPerDose, 1),
                        'total_units' => $totalUnits,
                        'fefo_deductions' => $deductions,
                    ],
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Kê đơn thất bại: ' . $e->getMessage()], 400);
        }
    }
}
