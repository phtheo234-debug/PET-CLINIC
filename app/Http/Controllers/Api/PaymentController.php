<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Thanh toán POS: gom các đơn thuốc chưa thanh toán của thú cưng thành 1 hóa đơn,
     * sau đó cho thú cưng xuất viện và trả lồng.
     */
    public function createInvoiceAndCheckout(Request $request)
    {
        $request->validate([
            'pet_id' => 'required|integer|exists:pets,pet_id',
            'payment_method' => 'required|in:TIEN_MAT,CHUYEN_KHOAN,THE',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $pet = DB::table('pets')->where('pet_id', $request->pet_id)->lockForUpdate()->first();

                $lines = DB::table('prescriptions as rx')
                    ->join('medical_records as mr', 'mr.record_id', '=', 'rx.record_id')
                    ->join('inventory as i', 'i.item_id', '=', 'rx.item_id')
                    ->where('mr.pet_id', $pet->pet_id)
                    ->whereNull('rx.invoice_id')
                    ->select('rx.prescription_id', DB::raw('rx.soluong * i.dongia as thanh_tien'))
                    ->get();

                if ($lines->isEmpty()) {
                    return response()->json(['status' => false, 'message' => 'Thú cưng này chưa có khoản nào cần thanh toán.'], 422);
                }

                $total = $lines->sum(fn ($l) => (float) $l->thanh_tien);

                $invoiceId = DB::table('invoices')->insertGetId([
                    'mahoadon' => 'TMP' . uniqid(),
                    'pet_id' => $pet->pet_id,
                    'owner_id' => $pet->owner_id,
                    'tongtien' => $total,
                    'phuongthucthanhtoan' => $request->payment_method,
                    'trangthai' => 'DA_THANH_TOAN',
                ]);
                $code = sprintf('HD%05d', $invoiceId);
                DB::table('invoices')->where('invoice_id', $invoiceId)->update(['mahoadon' => $code]);

                DB::table('prescriptions')
                    ->whereIn('prescription_id', $lines->pluck('prescription_id'))
                    ->update(['invoice_id' => $invoiceId]);

                // Xuất viện: trả lồng
                if ($pet->cage_id) {
                    DB::table('pet_boardings')
                        ->where('pet_id', $pet->pet_id)->where('trangthai', 'DANG_O')
                        ->update(['check_out' => now(), 'trangthai' => 'DA_RA']);
                    DB::table('cages')->where('cage_id', $pet->cage_id)
                        ->update(['soluonghientai' => DB::raw('GREATEST(soluonghientai - 1, 0)')]);
                    DB::table('cages')->where('cage_id', $pet->cage_id)
                        ->where('soluonghientai', 0)->where('trangthai', 'DA_CO_THU')
                        ->update(['trangthai' => 'CON_TRONG']);
                }
                DB::table('pets')->where('pet_id', $pet->pet_id)
                    ->update(['cage_id' => null, 'trangthai' => 'DA_XUAT_VIEN']);

                return response()->json([
                    'status' => true,
                    'message' => 'Thanh toán thành công, thú cưng đã xuất viện!',
                    'invoice_id' => $invoiceId,
                    'data' => ['invoice_id' => $invoiceId, 'mahoadon' => $code, 'tongtien' => $total],
                ]);
            });
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Thanh toán thất bại: ' . $e->getMessage()], 500);
        }
    }
}
