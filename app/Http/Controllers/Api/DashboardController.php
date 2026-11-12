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
        $tuNgay  = $request->query('tu_ngay', Carbon::now()->startOfMonth()->toDateString());
        $denNgay = $request->query('den_ngay', Carbon::now()->toDateString());

        $doanhThu = DB::table('invoices')
            ->select(DB::raw('DATE(ngaytao) as ngay'), DB::raw('SUM(tongtien) as tong_doanh_thu'))
            ->where('trangthai', 'DA_THANH_TOAN')
            ->whereBetween(DB::raw('DATE(ngaytao)'), [$tuNgay, $denNgay])
            ->groupBy(DB::raw('DATE(ngaytao)'))
            ->orderBy('ngay', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $doanhThu
        ]);
    }
}