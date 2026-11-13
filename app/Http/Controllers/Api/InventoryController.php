<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Lấy danh mục Thuốc, Vắc-xin, Vật tư & Dịch vụ Spa
     */
    public function index()
    {
        $items = DB::table('inventory')->get();

        foreach ($items as $item) {
            if ($item->danhmuc !== 'DICH_VU_SPA') {
                $item->batches = DB::table('inventory_batches')
                    ->where('item_id', $item->item_id)
                    ->orderBy('ngayhethan')
                    ->orderBy('batch_id')
                    ->get();
                $item->tong_ton_kho = (int) DB::table('inventory_batches')
                    ->where('item_id', $item->item_id)
                    ->where('trangthai', 'DANG_SU_DUNG')
                    ->where('soluong', '>', 0)
                    ->whereDate('ngayhethan', '>=', now()->toDateString())
                    ->sum('soluong');
                $firstUsableBatch = $item->batches->first(function ($batch) {
                    return $batch->trangthai === 'DANG_SU_DUNG'
                        && (int) $batch->soluong > 0
                        && $batch->ngayhethan >= now()->toDateString();
                });
                $item->solo = $firstUsableBatch->solo ?? null;
                $item->ngayhethan = $firstUsableBatch->ngayhethan ?? null;
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $items
        ]);
    }

    /**
     * Khai báo mặt hàng hoặc Dịch vụ Spa mới vào danh mục
     */
    public function store(Request $request)
    {
        
        $fields = $request->validate([
            'masanpham'       => 'required|string|max:20|unique:inventory,masanpham',
            'ten_sanpham'     => 'required|string|max:100',
            'danhmuc'         => 'required|in:THUOC,VAC_XIN,THUC_AN,VAT_TU,DICH_VU_SPA',
            'donvitinh'       => 'required|string|max:20',
            'dongia'          => 'required|numeric|min:0',
            'tonkhotoithieu'  => 'nullable|integer|min:0',

            // Cấu hình liều dùng, bắt buộc nếu là thuốc
            'lieu_mg_kg'      => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
            'mg_moi_don_vi'   => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
        ]);


        $id = DB::table('inventory')->insertGetId([
            'masanpham'       => $fields['masanpham'],
            'ten_sanpham'     => $fields['ten_sanpham'],
            'danhmuc'         => $fields['danhmuc'],
            'lieu_mg_kg'    => $fields['lieu_mg_kg'] ?? null,
            'mg_moi_don_vi' => $fields['mg_moi_don_vi'] ?? null,
            'donvitinh'       => $fields['donvitinh'],
            'dongia'          => $fields['dongia'],
            'tonkhotoithieu' => $fields['tonkhotoithieu'] ?? 10,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Thêm danh mục sản phẩm/dịch vụ thành công!',
            'data'    => DB::table('inventory')->where('item_id', $id)->first()
        ], 201);
    }

    /**
     * Nhập lô hàng mới (Theo Số lô & Hạn sử dụng FEFO)
     */
    public function importBatch(Request $request)
    {
        $fields = $request->validate([
            'item_id'    => 'required|integer|exists:inventory,item_id',
            'solo'       => 'required|string|max:50',
            'soluong'    => 'required|integer|min:1',
            'ngaynhap'   => 'required|date|before_or_equal:today',
            'ngayhethan' => 'required|date|after:ngaynhap|after_or_equal:today',
        ]);

        $batchId = DB::transaction(function () use ($fields) {
            $item = DB::table('inventory')
                ->where('item_id', $fields['item_id'])
                ->lockForUpdate()
                ->first();
            if (!$item || $item->danhmuc === 'DICH_VU_SPA') {
                throw new Exception('Chỉ được nhập lô cho mặt hàng hàng hóa trong kho.');
            }

            $duplicateBatch = DB::table('inventory_batches')
                ->where('item_id', $item->item_id)
                ->where('solo', $fields['solo'])
                ->exists();
            if ($duplicateBatch) {
                throw new Exception('Mã lô này đã tồn tại cho mặt hàng đã chọn.');
            }

            return DB::table('inventory_batches')->insertGetId([
                'item_id'    => $fields['item_id'],
                'solo'       => $fields['solo'],
                'soluong'    => $fields['soluong'],
                'ngaynhap'   => $fields['ngaynhap'],
                'ngayhethan' => $fields['ngayhethan'],
                'trangthai'  => 'DANG_SU_DUNG',
            ]);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Nhập lô hàng thành công!',
            'data'    => DB::table('inventory_batches')->where('batch_id', $batchId)->first()
        ], 201);
    }

    /**
     * API Xuất kho theo quy tắc FEFO (dùng cho bán lẻ hoặc xuất trực tiếp)
     */
    public function exportMedicineFEFO(Request $request)
    {
        $request->validate([
            'medicine_id'  => 'required|integer|exists:inventory,item_id',
            'required_qty' => 'required|integer|min:1',
        ]);

        try {
            $details = DB::transaction(
                fn () => $this->fefoDeduct((int) $request->medicine_id, (int) $request->required_qty)
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Xuất kho FEFO thành công!',
                'data'    => $details
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Hàm trừ kho FEFO an toàn - Dùng chung trong Transaction (Kê đơn / Xuất bán)
     */
    public function fefoDeduct(int $itemId, int $qty): array
    {
        if ($qty < 1) {
            throw new Exception('Số lượng xuất phải lớn hơn 0.');
        }

        $item = DB::table('inventory')->where('item_id', $itemId)->lockForUpdate()->first();
        if (!$item || $item->danhmuc === 'DICH_VU_SPA') {
            throw new Exception('Mặt hàng không hợp lệ để xuất kho.');
        }

        $batches = DB::table('inventory_batches')
            ->where('item_id', $itemId)
            ->where('soluong', '>', 0)
            ->where('trangthai', 'DANG_SU_DUNG')
            ->whereDate('ngayhethan', '>=', now()->toDateString())
            ->orderBy('ngayhethan')
            ->orderBy('batch_id')
            ->lockForUpdate()
            ->get();

        $remaining = $qty;
        $details = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($batch->soluong, $remaining);

            // Trừ số lượng kho của lô bằng decrement
            DB::table('inventory_batches')
                ->where('batch_id', $batch->batch_id)
                ->decrement('soluong', $take);

            $details[] = [
                'batch_id'       => $batch->batch_id,
                'batch_code'     => $batch->solo,
                'expiry_date'    => $batch->ngayhethan,
                'taken_quantity' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new Exception('Kho không đủ số lượng yêu cầu! Còn thiếu: ' . $remaining);
        }

        return $details;
    }
}