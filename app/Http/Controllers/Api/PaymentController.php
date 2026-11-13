<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CageOccupancyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function __construct(private CageOccupancyService $cageOccupancy)
    {
    }

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
            ->select(
                'i.*',
                'p.ten_thucung',
                'p.mathucung',
                'p.loai',
                'o.ho_ten as ten_chu_nuoi',
                'o.phone',
                'o.diachi'
            )
            ->first();

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy hóa đơn!'
            ], 404);
        }

        // Lấy chi tiết thuốc và phí lưu trú đã lưu tại thời điểm thanh toán
        $details = DB::table('invoice_details')
            ->where('invoice_id', $id)
            ->select(
                'detail_id',
                'loai_chiphi',
                'ten_sanpham',
                'soluong',
                'donvitinh',
                'dongia',
                'thanhtien',
                'ghichu'
            )
            ->orderBy('detail_id')
            ->get();

        $invoice->chi_tiet = $details;

        // Giữ tương thích với giao diện đang dùng tên cũ
        $invoice->chi_tiet_thuoc = $details;

        return response()->json([
            'status' => 'success',
            'data' => $invoice
        ]);
    }


    /** Danh sách các phiếu khám ngoại trú chưa lập hóa đơn. */
    public function pendingVisits()
    {
        $visits = DB::table('medical_records as mr')
            ->join('pets as p', 'p.pet_id', '=', 'mr.pet_id')
            ->leftJoin('owners as o', 'o.owner_id', '=', 'p.owner_id')
            ->whereNull('mr.invoice_id')
            ->where('mr.trangthai', 'HOAN_THANH')
            ->where('p.trangthai', '!=', 'NOI_TRU')
            ->select('mr.record_id', 'mr.pet_id', 'mr.ngaykham', 'mr.chandoan', 'p.mathucung', 'p.ten_thucung', 'o.ho_ten as ten_chu_nuoi')
            ->orderByDesc('mr.ngaykham')
            ->get();
        return response()->json(['status' => 'success', 'data' => $visits]);
    }

    /** Lập hóa đơn riêng cho một lượt khám ngoại trú, không làm thủ tục xuất viện. */
    public function createVisitInvoice(Request $request)
    {
        $fields = $request->validate([
            'record_id' => 'required|integer|exists:medical_records,record_id',
            'payment_method' => 'required|in:TIEN_MAT,CHUYEN_KHOAN,THE',
        ]);

        return DB::transaction(function () use ($fields) {
            $record = DB::table('medical_records')->where('record_id', $fields['record_id'])->lockForUpdate()->first();
            if (!$record || $record->invoice_id) {
                return response()->json(['status' => 'error', 'message' => 'Phiếu khám này đã được thanh toán hoặc không còn tồn tại.'], 422);
            }
            if ($record->trangthai !== 'HOAN_THANH') {
                return response()->json(['status' => 'error', 'message' => 'Chỉ thanh toán phiếu khám đã hoàn thành.'], 422);
            }
            $pet = DB::table('pets')->where('pet_id', $record->pet_id)->lockForUpdate()->first();
            if (!$pet || $pet->trangthai === 'NOI_TRU') {
                return response()->json(['status' => 'error', 'message' => 'Thú cưng đang nội trú phải thanh toán tại mục xuất viện.'], 422);
            }
            $items = DB::table('prescriptions as rx')
                ->join('inventory as i', 'i.item_id', '=', 'rx.item_id')
                ->where('rx.record_id', $record->record_id)
                ->whereNull('rx.invoice_id')
                ->select('rx.prescription_id', 'rx.soluong', 'i.danhmuc', 'i.ten_sanpham', 'i.donvitinh', 'i.dongia')
                ->lockForUpdate()->get();
            $examFee = 50000;
            $medicineTotal = $items->sum(fn ($item) => (float) $item->soluong * (float) $item->dongia);
            $invoiceId = DB::table('invoices')->insertGetId([
                'mahoadon' => 'TMP' . uniqid(), 'pet_id' => $pet->pet_id, 'owner_id' => $pet->owner_id,
                'tongtien' => $examFee + $medicineTotal, 'phuongthucthanhtoan' => $fields['payment_method'],
                'trangthai' => 'DA_THANH_TOAN', 'ngaytao' => now(),
            ]);
            $code = sprintf('HD%05d', $invoiceId);
            DB::table('invoices')->where('invoice_id', $invoiceId)->update(['mahoadon' => $code]);
            DB::table('invoice_details')->insert([
                'invoice_id' => $invoiceId, 'prescription_id' => null, 'loai_chiphi' => 'PHI_KHAM',
                'ten_sanpham' => 'Phí khám bệnh - Phiếu #' . $record->record_id, 'donvitinh' => 'lần',
                'soluong' => 1, 'dongia' => $examFee, 'thanhtien' => $examFee,
                'ghichu' => 'Ngày khám: ' . Carbon::parse($record->ngaykham)->format('d/m/Y H:i'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($items as $item) {
                DB::table('invoice_details')->insert([
                    'invoice_id' => $invoiceId, 'prescription_id' => $item->prescription_id,
                    'loai_chiphi' => $item->danhmuc === 'VAC_XIN' ? 'VAC_XIN' : 'THUOC',
                    'ten_sanpham' => $item->ten_sanpham, 'donvitinh' => $item->donvitinh,
                    'soluong' => $item->soluong, 'dongia' => $item->dongia,
                    'thanhtien' => (float) $item->soluong * (float) $item->dongia,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($items->isNotEmpty()) {
                DB::table('prescriptions')->whereIn('prescription_id', $items->pluck('prescription_id'))->update(['invoice_id' => $invoiceId]);
            }
            DB::table('medical_records')->where('record_id', $record->record_id)->update(['invoice_id' => $invoiceId]);
            return response()->json(['status' => 'success', 'message' => 'Đã thanh toán lượt khám ngoại trú.', 'data' => [
                'invoice_id' => $invoiceId, 'mahoadon' => $code, 'tien_kham' => $examFee,
                'tien_thuoc' => $medicineTotal, 'tong_thanhtoan' => $examFee + $medicineTotal,
            ]], 201);
        });
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

                // 1. Kiểm tra trạng thái hiện tại của thú cưng
                if ($pet->trangthai === 'DA_XUAT_VIEN') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng này đã xuất viện, không thể thanh toán và xuất viện lần nữa!'
                    ], 422);
                }

                if ($pet->trangthai === 'CHUYEN_VIEN') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng đã được chuyển viện, không thể thực hiện xuất viện tại đây!'
                    ], 422);
                }

                if ($pet->trangthai !== 'NOI_TRU') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Chỉ thú cưng đang nội trú mới được thanh toán và làm thủ tục xuất viện!'
                    ], 422);
                }

                // 2. Kiểm tra hồ sơ khám gần nhất
                $latestRecord = DB::table('medical_records')
                    ->where('pet_id', $pet->pet_id)
                    ->orderByDesc('ngaykham')
                    ->orderByDesc('record_id')
                    ->first();

                // Bắt buộc phải có hồ sơ khám
                if (!$latestRecord) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Thú cưng chưa có hồ sơ khám bệnh. Không thể thực hiện xuất viện!'
                    ], 422);
                }

                // Không cho xuất viện khi vẫn đang điều trị
                if ($latestRecord->trangthai === 'DANG_DIEU_TRI') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Vi phạm BR-06: Thú cưng đang trong quá trình điều trị tích cực, Bác sĩ chưa cho phép xuất viện!'
                    ], 422);
                }

                // Không cho xuất viện nếu đã có kết luận chuyển viện
                if ($latestRecord->trangthai === 'CHUYEN_VIEN') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Hồ sơ khám có kết luận chuyển viện. Không thể thực hiện xuất viện tại đây!'
                    ], 422);
                }

                // Chỉ cho phép xuất viện khi bác sĩ đã hoàn thành hồ sơ
                if ($latestRecord->trangthai !== 'HOAN_THANH') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Hồ sơ khám chưa đủ điều kiện xuất viện!'
                    ], 422);
                }

                // 3. Kiểm tra thú cưng nội trú phải có lồng lưu trú
                if (!$pet->cage_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Dữ liệu nội trú không hợp lệ: thú cưng chưa được xếp vào lồng lưu trú!'
                    ], 422);
                }

                $activeBoarding = DB::table('pet_boardings')
                    ->where('pet_id', $pet->pet_id)
                    ->where('cage_id', $pet->cage_id)
                    ->where('status', 'checked_in')
                    ->whereNull('check_out')
                    ->lockForUpdate()
                    ->first();

                if (!$activeBoarding) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Không tìm thấy lượt lưu trú đang hoạt động của thú cưng. Vui lòng kiểm tra lại lịch sử xếp lồng!'
                    ], 422);
                }

                // Khóa lồng để tránh nhiều yêu cầu cùng trả một lồng
                $cage = DB::table('cages')
                    ->where('cage_id', $pet->cage_id)
                    ->lockForUpdate()
                    ->first();

                if (!$cage) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Không tìm thấy lồng lưu trú của thú cưng!'
                    ], 422);
                }

                if ($cage->soluonghientai <= 0) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Số lượng thú trong lồng không hợp lệ. Vui lòng kiểm tra lại!'
                    ], 422);
                }

                // 2. Gom chi phí các đơn thuốc chưa thanh toán
                $lines = DB::table('prescriptions as rx')
                    ->join('medical_records as mr', 'mr.record_id', '=', 'rx.record_id')
                    ->join('inventory as i', 'i.item_id', '=', 'rx.item_id')
                    ->where('mr.pet_id', $pet->pet_id)
                    ->whereNull('rx.invoice_id')
                    ->select(
                        'rx.prescription_id',
                        'rx.soluong',
                        'i.ten_sanpham',
                        'i.donvitinh',
                        'i.dongia as don_gia',
                        'i.danhmuc',
                        DB::raw('rx.soluong * i.dongia as thanh_tien')
                    )
                    ->lockForUpdate()
                    ->get();

                $totalMedicine = $lines->sum(function ($line) {
                    return (float) $line->thanh_tien;
                });


                // 3. Lấy các dịch vụ Spa đã ghi nhận nhưng chưa đưa vào hóa đơn
                $spaLines = DB::table('spa_usages')
                    ->where('pet_id', $pet->pet_id)
                    ->whereNull('invoice_id')
                    ->lockForUpdate()
                    ->get();

                $totalSpa = $spaLines->sum(function ($line) {
                    return (float) $line->thanhtien;
                });

                // 4. Tính tiền lồng lưu trú (nếu có nội trú)
                $checkInDate = Carbon::parse($activeBoarding->check_in);

                // Tính số ngày lưu trú, làm tròn lên và tối thiểu 1 ngày
                $days = max(
                    1,
                    (int) ceil($checkInDate->diffInHours(now()) / 24)
                );

                $pricePerDay = 100000;
                $totalBoarding = $days * $pricePerDay;


                                // Phí khám cố định 50.000 đồng cho mỗi phiếu khám chưa lập hóa đơn.
                $unbilledRecords = DB::table('medical_records')
                    ->where('pet_id', $pet->pet_id)
                    ->whereNull('invoice_id')
                    ->orderBy('ngaykham')
                    ->lockForUpdate()
                    ->get();
                $examFeePerVisit = 50000;
                $totalExam = $unbilledRecords->count() * $examFeePerVisit;
                $grandTotal = $totalMedicine + $totalBoarding + $totalSpa + $totalExam;

                if ($grandTotal <= 0 && $lines->isEmpty() && $unbilledRecords->isEmpty()) {
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

                DB::table('invoices')
                    ->where('invoice_id', $invoiceId)
                    ->update([
                        'mahoadon' => $code
                    ]);
                
                // 5. Lưu chi tiết hóa đơn và đánh dấu đơn thuốc đã thanh toán
                if ($lines->isNotEmpty()) {
                    foreach ($lines as $line) {
                        DB::table('invoice_details')->insert([
                            'invoice_id'      => $invoiceId,
                            'prescription_id' => $line->prescription_id,
                            'loai_chiphi'     => $line->danhmuc === 'VAC_XIN' ? 'VAC_XIN' : 'THUOC',
                            'ten_sanpham'     => $line->ten_sanpham,
                            'donvitinh'       => $line->donvitinh,
                            'soluong'         => $line->soluong,
                            'dongia'          => $line->don_gia,
                            'thanhtien'       => $line->thanh_tien,
                            'ghichu'          => null,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }

                    // Đánh dấu các đơn thuốc đã được lập hóa đơn
                    DB::table('prescriptions')
                        ->whereIn(
                            'prescription_id',
                            $lines->pluck('prescription_id')
                        )
                        ->update([
                            'invoice_id' => $invoiceId
                        ]);
                }

                // Lưu riêng phí khám cho từng lần khám.
                foreach ($unbilledRecords as $examRecord) {
                    DB::table('invoice_details')->insert([
                        'invoice_id' => $invoiceId, 'prescription_id' => null, 'loai_chiphi' => 'PHI_KHAM',
                        'ten_sanpham' => 'Phí khám bệnh - Phiếu #' . $examRecord->record_id,
                        'donvitinh' => 'lần', 'soluong' => 1, 'dongia' => $examFeePerVisit,
                        'thanhtien' => $examFeePerVisit,
                        'ghichu' => 'Ngày khám: ' . Carbon::parse($examRecord->ngaykham)->format('d/m/Y H:i'),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if ($unbilledRecords->isNotEmpty()) {
                    DB::table('medical_records')->whereIn('record_id', $unbilledRecords->pluck('record_id'))->update(['invoice_id' => $invoiceId]);
                }
                // 6. Lưu chi tiết phí lưu trú
                if ($totalBoarding > 0) {
                    DB::table('invoice_details')->insert([
                        'invoice_id'      => $invoiceId,
                        'prescription_id' => null,
                        'loai_chiphi'     => 'LUU_TRU',
                        'ten_sanpham'     => 'Phí lưu trú lồng ' . $cage->malong,
                        'donvitinh'       => 'ngày',
                        'soluong'         => $days,
                        'dongia'          => $pricePerDay,
                        'thanhtien'       => $totalBoarding,
                        'ghichu'          => 'Từ ngày '
                            . $checkInDate->format('d/m/Y H:i')
                            . ' đến ngày '
                            . now()->format('d/m/Y H:i'),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                // 7. Lưu chi tiết dịch vụ Spa và đánh dấu đã lập hóa đơn
                if ($spaLines->isNotEmpty()) {
                    foreach ($spaLines as $spaLine) {
                        DB::table('invoice_details')->insert([
                            'invoice_id'      => $invoiceId,
                            'prescription_id' => null,
                            'loai_chiphi'     => 'DICH_VU_SPA',
                            'ten_sanpham'     => $spaLine->ten_dichvu,
                            'donvitinh'       => $spaLine->donvitinh,
                            'soluong'         => $spaLine->soluong,
                            'dongia'          => $spaLine->dongia,
                            'thanhtien'       => $spaLine->thanhtien,
                            'ghichu'          => $spaLine->ghichu,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }

                    DB::table('spa_usages')
                        ->whereIn('spa_usage_id', $spaLines->pluck('spa_usage_id'))
                        ->whereNull('invoice_id')
                        ->update(['invoice_id' => $invoiceId, 'updated_at' => now()]);
                }

                // 8. Trả lồng và cập nhật trạng thái
                DB::table('pet_boardings')
                    ->where('id', $activeBoarding->id)
                    ->whereNull('check_out')
                    ->update([
                        'check_out'  => now(),
                        'status'     => 'checked_out',
                        'updated_at' => now(),
                    ]);

                // Đóng yêu cầu lưu trú tương ứng sau khi hoàn tất thanh toán và xuất viện.
                DB::table('boarding_requests')
                    ->where('pet_id', $pet->pet_id)
                    ->where('status', 'checked_in')
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now(),
                    ]);

                // 7. Cập nhật trạng thái thú cưng trước, sau đó đồng bộ lồng
                DB::table('pets')->where('pet_id', $pet->pet_id)->update([
                    'cage_id'   => null,
                    'trangthai' => 'DA_XUAT_VIEN'
                ]);

                $updatedCage = $this->cageOccupancy->sync((int) $cage->cage_id);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Thanh toán thành công, lồng đã được giải phóng và thú cưng xuất viện!',
                    'data'    => [
                        'invoice_id'     => $invoiceId,
                        'mahoadon'       => $code,
                        'tien_thuoc'     => $totalMedicine,
                        'tien_kham'       => $totalExam,
                        'so_lan_kham'     => $unbilledRecords->count(),
                        'tien_long'      => $totalBoarding,
                        'tien_spa'       => $totalSpa,
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