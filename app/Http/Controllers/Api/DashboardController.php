<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * FR-STAT-01: Chỉ số Dashboard tổng quan thời gian thực
     */
    public function getOverview()
    {
        $today = Carbon::today();

        $soCaKhamNgay = DB::table('medical_records')->whereDate('ngaykham', $today)->count();
        $soThuNoiTru  = DB::table('pets')->where('trangthai', 'NOI_TRU')->count();
        $soLongTrong  = DB::table('cages')->where('trangthai', 'CON_TRONG')->count();
        $doanhThuNgay = DB::table('invoices')->where('trangthai', 'DA_THANH_TOAN')->whereDate('ngaytao', $today)->sum('tongtien');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'so_ca_kham_ngay' => $soCaKhamNgay,
                'so_thu_noi_tru'  => $soThuNoiTru,
                'so_long_trong'   => $soLongTrong,
                'doanh_thu_ngay'  => (float)$doanhThuNgay,
            ]
        ]);
    }

    /**
     * Cảnh báo hạn sử dụng thuốc, vắc-xin và thức ăn.
     * Hàng hết hạn tự động chuyển trạng thái HET_HAN, không còn được xuất/bán.
     */
    public function getInventoryAlerts()
    {
        $today = Carbon::today();
        $sixMonths = Carbon::today()->addMonths(6);

        DB::table('inventory_batches')
            ->whereNotNull('ngayhethan')
            ->whereDate('ngayhethan', '<', $today->toDateString())
            ->where('trangthai', 'DANG_SU_DUNG')
            ->update(['trangthai' => 'HET_HAN']);

        $base = DB::table('inventory_batches as b')
            ->join('inventory as i', 'i.item_id', '=', 'b.item_id')
            ->whereIn('i.danhmuc', ['THUOC', 'VAC_XIN', 'THUC_AN'])
            ->where('b.soluong', '>', 0)
            ->select(
                'b.batch_id', 'i.masanpham', 'i.ten_sanpham', 'i.danhmuc',
                'b.solo', 'b.soluong', 'b.ngayhethan', 'b.trangthai'
            );

        $soonExpiry = (clone $base)
            ->where('b.trangthai', 'DANG_SU_DUNG')
            ->whereDate('b.ngayhethan', '>=', $today->toDateString())
            ->whereDate('b.ngayhethan', '<', $sixMonths->toDateString())
            ->orderBy('b.ngayhethan')
            ->get();

        $expired = (clone $base)
            ->where('b.trangthai', 'HET_HAN')
            ->whereDate('b.ngayhethan', '<', $today->toDateString())
            ->orderBy('b.ngayhethan')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'sap_het_han' => $soonExpiry,
                'da_het_han' => $expired,
                'so_sap_het_han' => $soonExpiry->count(),
                'so_da_het_han' => $expired->count(),
            ],
        ]);
    }

    /**
     * FR-STAT-04: Thống kê doanh thu chi tiết theo khoảng thời gian
     */
    public function getRevenueReport(Request $request)
    {
        $validated = $request->validate([
            'tu_ngay' => 'nullable|date',
            'den_ngay' => 'nullable|date|after_or_equal:tu_ngay',
        ]);

        $tuNgay = $validated['tu_ngay'] ?? Carbon::now()->startOfMonth()->toDateString();
        $denNgay = $validated['den_ngay'] ?? Carbon::now()->toDateString();

        $invoiceQuery = DB::table('invoices')
            ->where('trangthai', 'DA_THANH_TOAN')
            ->whereBetween(DB::raw('DATE(ngaytao)'), [$tuNgay, $denNgay]);

        $tongDoanhThu = (clone $invoiceQuery)->sum('tongtien');
        $soHoaDon = (clone $invoiceQuery)->count();

        $doanhThuTheoNgay = (clone $invoiceQuery)
            ->select(
                DB::raw('DATE(ngaytao) as ngay'),
                DB::raw('SUM(tongtien) as tong_doanh_thu'),
                DB::raw('COUNT(*) as so_hoa_don')
            )
            ->groupBy(DB::raw('DATE(ngaytao)'))
            ->orderBy('ngay', 'asc')
            ->get();

        $doanhThuTheoLoai = DB::table('invoice_details as detail')
            ->join('invoices as invoice', 'invoice.invoice_id', '=', 'detail.invoice_id')
            ->where('invoice.trangthai', 'DA_THANH_TOAN')
            ->whereBetween(DB::raw('DATE(invoice.ngaytao)'), [$tuNgay, $denNgay])
            ->select(
                'detail.loai_chiphi',
                DB::raw('SUM(detail.thanhtien) as tong_doanh_thu'),
                DB::raw('COUNT(DISTINCT invoice.invoice_id) as so_hoa_don')
            )
            ->groupBy('detail.loai_chiphi')
            ->orderByDesc('tong_doanh_thu')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'tong_doanh_thu' => (float) $tongDoanhThu,
                'so_hoa_don' => (int) $soHoaDon,
                'doanh_thu_trung_binh' => $soHoaDon > 0 ? (float) $tongDoanhThu / $soHoaDon : 0,
                'theo_ngay' => $doanhThuTheoNgay,
                'theo_loai_chi_phi' => $doanhThuTheoLoai,
            ],
        ]);
    }
}
