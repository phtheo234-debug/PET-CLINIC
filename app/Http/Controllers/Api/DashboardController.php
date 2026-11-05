<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getOverview()
    {
        $today = Carbon::today();

        $soCaKhamNgay = DB::table('medical_records')->whereDate('ngaykham', $today)->count();
        $soThuNoiTru  = DB::table('pets')->where('trangthai', 'NOI_TRU')->count();
        $soLongTrong  = DB::table('cages')->where('trangthai', 'CON_TRONG')->count();
        $doanhThuNgay = DB::table('invoices')->where('trangthai', 'DA_THANH_TOAN')->whereDate('ngaytao', $today)->sum('tongtien');

        return response()->json([
            'status'=> 'success',
            'data'=> [
                'so_ca_kham_ngay'=> $soCaKhamNgay,
                'so_thu_noi_tru'=> $soThuNoiTru,
                'so_long_trong'=> $soLongTrong,
                'doanh_thu_ngay'=> (float)$doanhThuNgay,
            ]
        ]);
    }
}