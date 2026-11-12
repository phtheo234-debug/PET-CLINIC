<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * FR-BILL-03: Tra cứu danh sách & Chi tiết Hóa đơn thanh toán
     */
    public function index()
    {
        $invoices = DB::table('invoices as i')
            ->join('pets as p', 'p.pet_id', '=', 'i.pet_id')
            ->join('owners as o', 'o.owner_id', '=', 'i.owner_id')
            ->select('i.*', 'p.ten_thucung', 'p.mathucung', 'o.ho_ten as ten_chu_nuoi', 'o.phone')
            ->orderByDesc('i.ngaytao')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $invoices
        ]);
    }

    public function show($id)
    {
        $invoice = DB::table('invoices as i')
            ->join('pets as p', 'p.pet_id', '=', 'i.pet_id')
            ->join('owners as o', 'o.owner_id', '=', 'i.owner_id')
            ->where('i.invoice_id', $id)
            ->select('i.*', 'p.ten_thucung', 'p.mathucung', 'p.loai', 'o.ho_ten as ten_chu_nuoi', 'o.phone', 'o.diachi')
            ->first();

        if (!$invoice) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy hóa đơn!'], 404);
        }

        // Lấy chi tiết đơn thuốc trong hóa đơn
        $prescriptions = DB::table('prescriptions as rx')
            ->join('inventory as inv', 'inv.item_id', '=', 'rx.item_id')
            ->where('rx.invoice_id', $id)
            ->select('inv.ten_sanpham', 'rx.soluong', 'inv.donvitinh', 'inv.dongia', DB::raw('rx.soluong * inv.dongia as thanh_tien'))
            ->get();

        $invoice->chi_tiet_thuoc = $prescriptions;

        return response()->json([
            'status' => 'success',
            'data'   => $invoice
        ]);
    }

    /**
     * FR-BILL-02: Lập Hóa đơn Thanh toán POS & Duyệt Xuất viện (BR-05, BR-06)
     */
    public function createInvoiceAndCheckout(Request $request)
    {
        $request->validate([
            'pet_id'         => 'required|integer|exists:pets,pet_id',
            'payment_method' => 'required|in:TIEN_MAT,CHUYEN_KHOAN,THE',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $pet = DB::table('pets')->where('pet_id', $request->pet_id)->lockForUpdate()->first();

                // 1. Kiểm tra điều kiện an toàn BR-06: Hồ sơ khám gần nhất phải HOAN_THANH hoặc không còn DANG_DIEU_TRI
                $latestRecord = DB::table('medical_records')
                    ->where('pet_id', $pet->pet_id)
                    ->orderByDesc('ngaykham')
                    ->first();

                if ($latestRecord && $latestRecord->trangthai === 'DANG_DIEU_TRI') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Vi phạm BR-06: Thú cưng đang trong quá trình điều trị tích cực, Bác sĩ chưa cho phép xuất viện!'
                    ], 422);
                }

                // 2. Gom chi phí các đơn thuốc chưa thanh toán
                $lines = DB::table('prescriptions as rx')
                    ->join('medical_records as mr', 'mr.record_id', '=', 'rx.record_id')
                    ->join('inventory as i', 'i.item_id', '=', 'rx.item_id')
                    ->where('mr.pet_id', $pet->pet_id)
                    ->whereNull('rx.invoice_id')
                    ->select('rx.prescription_id', DB::raw('rx.soluong * i.dongia as thanh_tien'))
                    ->get();

                $totalMedicine = $lines->sum(fn ($l) => (float) $l->thanh_tien);

                // 3. Tính tiền lồng lưu trú (nếu có nội trú)
                $totalBoarding = 0;
                $activeBoarding = DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->whereNull('check_out')
                    ->first();

                if ($activeBoarding) {
                    $checkInDate = Carbon::parse($activeBoarding->check_in);
                    $days = max(1, $checkInDate->diffInDays(now()));
                    $pricePerDay = 100000; // Đơn giá lồng lưu trú mặc định/ngày
                    $totalBoarding = $days * $pricePerDay;
                }

                $grandTotal = $totalMedicine + $totalBoarding;

                if ($grandTotal <= 0 && $lines->isEmpty()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng này không có khoản chi phí nào cần thanh toán.'
                    ], 422);
                }

                // 4. Tạo Hóa đơn
                $invoiceId = DB::table('invoices')->insertGetId([
                    'mahoadon'            => 'TMP' . uniqid(),
                    'pet_id'              => $pet->pet_id,
                    'owner_id'            => $pet->owner_id,
                    'tongtien'            => $grandTotal,
                    'phuongthucthanhtoan' => $request->payment_method,
                    'trangthai'           => 'DA_THANH_TOAN',
                    'ngaytao'             => now(),
                ]);

                $code = sprintf('HD%05d', $invoiceId);
                DB::table('invoices')->where('invoice_id', $invoiceId)->update(['mahoadon' => $code]);

                // 5. Đánh dấu các đơn thuốc đã thuộc về hóa đơn này
                if ($lines->isNotEmpty()) {
                    DB::table('prescriptions')
                        ->whereIn('prescription_id', $lines->pluck('prescription_id'))
                        ->update(['invoice_id' => $invoiceId]);
                }

                // 6. Trả lồng và cập nhật trạng thái (Đáp ứng BR-05)
                if ($pet->cage_id) {
                    DB::table('pet_boardings')
                        ->where('pet_id', $pet->pet_id)
                        ->whereNull('check_out')
                        ->update(['check_out' => now()]);

                    $cage = DB::table('cages')->where('cage_id', $pet->cage_id)->first();
                    if ($cage) {
                        $newCount = max(0, $cage->soluonghientai - 1);
                        DB::table('cages')->where('cage_id', $pet->cage_id)->update([
                            'soluonghientai' => $newCount,
                            'trangthai'      => ($newCount == 0) ? 'CON_TRONG' : 'DA_CO_THU'
                        ]);
                    }
                }

                // 7. Cập nhật trạng thái Thú cưng xuất viện
                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id'   => null,
                    'trangthai' => 'DA_XUAT_VIEN'
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Thanh toán thành công, lồng đã được giải phóng và thú cưng xuất viện!',
                    'data'    => [
                        'invoice_id'     => $invoiceId,
                        'mahoadon'       => $code,
                        'tien_thuoc'     => $totalMedicine,
                        'tien_long'      => $totalBoarding,
                        'tong_thanhtoan' => $grandTotal,
                    ],
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Thanh toán thất bại: ' . $e->getMessage()
            ], 500);
        }
    }
}