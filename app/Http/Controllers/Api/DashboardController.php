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
     * FR-STAT-03: Cảnh báo thuốc/vắc-xin cận date (trong 30 ngày) hoặc hết hạn
     */
    public function getInventoryAlerts()
    {
        $canhBaoDate = DB::table('inventory_batches')
            ->join('inventory', 'inventory_batches.item_id', '=', 'inventory.item_id')
            ->select(
                'inventory.masanpham',
                'inventory.ten_sanpham',
                'inventory_batches.solo',
                'inventory_batches.soluong',
                'inventory_batches.ngayhethan',
                'inventory_batches.trangthai'
            )
            ->where('inventory_batches.ngayhethan', '<=', Carbon::now()->addDays(30))
            ->orderBy('inventory_batches.ngayhethan', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'canh_bao_date' => $canhBaoDate
            ]
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
}