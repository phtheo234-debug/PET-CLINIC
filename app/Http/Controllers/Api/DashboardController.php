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
            ->select('b.batch_id', 'i.masanpham', 'i.ten_sanpham', 'i.danhmuc',
                'b.solo', 'b.soluong', 'b.ngayhethan', 'b.trangthai');

        $soonExpiry = (clone $base)->where('b.trangthai', 'DANG_SU_DUNG')
            ->whereDate('b.ngayhethan', '>=', $today->toDateString())
            ->whereDate('b.ngayhethan', '<', $sixMonths->toDateString())
            ->orderBy('b.ngayhethan')->get();

        $expired = (clone $base)->where('b.trangthai', 'HET_HAN')
            ->whereDate('b.ngayhethan', '<', $today->toDateString())
            ->orderBy('b.ngayhethan')->get();

        // Tồn thuốc/vắc-xin/thức ăn được cộng từ các lô còn sử dụng;
        // đồ dùng lấy trực tiếp từ inventory.soluongton. Không đưa kho dịch vụ vào cảnh báo.
        $batchStocks = DB::table('inventory_batches')
            ->where('trangthai', 'DANG_SU_DUNG')
            ->where(function ($query) use ($today) {
                $query->whereNull('ngayhethan')
                    ->orWhereDate('ngayhethan', '>=', $today->toDateString());
            })
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(soluong) as tong_ton'));

        $lowStockRows = DB::table('inventory as i')
            ->leftJoinSub($batchStocks, 'stock', function ($join) {
                $join->on('stock.item_id', '=', 'i.item_id');
            })
            ->whereIn('i.danhmuc', ['THUOC', 'VAC_XIN', 'THUC_AN', 'DO_DUNG'])
            ->select('i.item_id', 'i.masanpham', 'i.ten_sanpham', 'i.danhmuc',
                DB::raw("CASE WHEN i.danhmuc = 'DO_DUNG' THEN COALESCE(i.soluongton, 0) ELSE COALESCE(stock.tong_ton, 0) END as so_luong"))
            ->having('so_luong', '<', 5)
            ->orderBy('i.danhmuc')->orderBy('i.ten_sanpham')->get();

        $khoSapHet = [
            'THUOC' => ['ten_kho' => 'Kho thuốc', 'items' => []],
            'VAC_XIN' => ['ten_kho' => 'Kho vắc-xin tiêm phòng', 'items' => []],
            'THUC_AN' => ['ten_kho' => 'Kho thức ăn', 'items' => []],
            'DO_DUNG' => ['ten_kho' => 'Kho đồ dùng', 'items' => []],
        ];
        foreach ($lowStockRows as $item) {
            $khoSapHet[$item->danhmuc]['items'][] = [
                'ma' => $item->masanpham,
                'ten' => $item->ten_sanpham,
                'so_luong' => (int) $item->so_luong,
            ];
        }
        foreach ($khoSapHet as $key => $store) {
            $khoSapHet[$key]['so_mat_hang'] = count($store['items']);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'sap_het_han' => $soonExpiry,
                'da_het_han' => $expired,
                'so_sap_het_han' => $soonExpiry->count(),
                'so_da_het_han' => $expired->count(),
                'kho_sap_het' => $khoSapHet,
            ],
        ]);
    }

    /**
     * Báo cáo doanh thu theo ngày, tuần, tháng và năm.
     */
    public function getRevenueReport(Request $request)
    {
        $validated = $request->validate([
            'ngay' => 'nullable|date',
            'tuan' => 'nullable|date',
            'thang' => 'nullable|date_format:Y-m',
            'nam' => 'nullable|integer|min:2000|max:2100',
        ]);

        $ngay = Carbon::parse($validated['ngay'] ?? Carbon::today())->startOfDay();
        $tuan = Carbon::parse($validated['tuan'] ?? Carbon::today())->startOfWeek(Carbon::MONDAY);
        $thang = Carbon::createFromFormat('Y-m-d', ($validated['thang'] ?? Carbon::today()->format('Y-m')) . '-01')->startOfMonth();
        $nam = (int) ($validated['nam'] ?? Carbon::today()->year);

        $paidInvoices = DB::table('invoices')->where('trangthai', 'DA_THANH_TOAN');
        $sumInvoices = function (Carbon $from, Carbon $to) use ($paidInvoices) {
            return (clone $paidInvoices)->whereBetween(DB::raw('DATE(ngaytao)'), [
                $from->toDateString(), $to->toDateString()
            ]);
        };
        $categoryTotals = function (Carbon $from, Carbon $to) {
            return DB::table('invoice_details as detail')
                ->join('invoices as invoice', 'invoice.invoice_id', '=', 'detail.invoice_id')
                ->where('invoice.trangthai', 'DA_THANH_TOAN')
                ->whereBetween(DB::raw('DATE(invoice.ngaytao)'), [$from->toDateString(), $to->toDateString()])
                ->select('detail.loai_chiphi',
                    DB::raw('SUM(detail.thanhtien) as tong_doanh_thu'),
                    DB::raw('COUNT(DISTINCT invoice.invoice_id) as so_hoa_don'))
                ->groupBy('detail.loai_chiphi')->orderByDesc('tong_doanh_thu')->get();
        };

        $dayInvoices = $sumInvoices($ngay, $ngay);
        $dayTotal = (float) (clone $dayInvoices)->sum('tongtien');
        $dayCount = (int) (clone $dayInvoices)->count();
        $dayCategories = $categoryTotals($ngay, $ngay);

        $weekEnd = $tuan->copy()->endOfWeek(Carbon::SUNDAY);
        $weekInvoices = $sumInvoices($tuan, $weekEnd);
        $weekTotal = (float) (clone $weekInvoices)->sum('tongtien');
        $weekCount = (int) (clone $weekInvoices)->count();
        $weeklyRows = [];
        for ($date = $tuan->copy(); $date->lte($weekEnd); $date->addDay()) {
            $row = $sumInvoices($date, $date);
            $weeklyRows[] = [
                'ngay' => $date->toDateString(),
                'tong_doanh_thu' => (float) (clone $row)->sum('tongtien'),
                'so_hoa_don' => (int) (clone $row)->count(),
            ];
        }

        $monthEnd = $thang->copy()->endOfMonth();
        $monthInvoices = $sumInvoices($thang, $monthEnd);
        $monthTotal = (float) (clone $monthInvoices)->sum('tongtien');
        $monthCount = (int) (clone $monthInvoices)->count();

        $yearStart = Carbon::create($nam, 1, 1)->startOfDay();
        $yearEnd = $yearStart->copy()->endOfYear();
        $yearInvoices = $sumInvoices($yearStart, $yearEnd);
        $yearTotal = (float) (clone $yearInvoices)->sum('tongtien');
        $yearCount = (int) (clone $yearInvoices)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ngay' => $ngay->toDateString(),
                'tong_doanh_thu_ngay' => $dayTotal,
                'so_hoa_don_ngay' => $dayCount,
                'so_ca_kham' => (int) DB::table('medical_records')->whereDate('ngaykham', $ngay->toDateString())->count(),
                'theo_loai_chi_phi_ngay' => $dayCategories,
                'tuan_tu_ngay' => $tuan->toDateString(),
                'tuan_den_ngay' => $weekEnd->toDateString(),
                'tong_doanh_thu_tuan' => $weekTotal,
                'so_hoa_don_tuan' => $weekCount,
                'theo_ngay_trong_tuan' => $weeklyRows,
                'thang' => $thang->format('Y-m'),
                'tong_doanh_thu_thang' => $monthTotal,
                'so_hoa_don_thang' => $monthCount,
                'theo_loai_chi_phi_thang' => $categoryTotals($thang, $monthEnd),
                'nam' => $nam,
                'tong_doanh_thu_nam' => $yearTotal,
                'so_hoa_don_nam' => $yearCount,
                'theo_loai_chi_phi_nam' => $categoryTotals($yearStart, $yearEnd),
            ],
        ]);
    }
}
