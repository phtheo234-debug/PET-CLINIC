<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Xuất kho theo FEFO (hết hạn trước, xuất trước). Bỏ qua lô đã hết hạn.
     * medicine_id = inventory.item_id
     */
    public function exportMedicineFEFO(Request $request)
    {
        $request->validate([
            'medicine_id' => 'required|integer|exists:inventory,item_id',
            'required_qty' => 'required|integer|min:1',
        ]);

        try {
            $details = DB::transaction(
                fn () => $this->fefoDeduct((int) $request->medicine_id, (int) $request->required_qty)
            );

            return response()->json(['status' => true, 'message' => 'Xuất kho FEFO thành công!', 'data' => $details], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Trừ kho theo FEFO. Phải gọi bên trong DB::transaction.
     * Ném Exception nếu kho không đủ, để giao dịch bên ngoài (ví dụ kê đơn) cũng được hoàn tác.
     */
    public function fefoDeduct(int $itemId, int $qty): array
    {
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

            DB::table('inventory_batches')
                ->where('batch_id', $batch->batch_id)
                ->update(['soluong' => $batch->soluong - $take]);

            $details[] = [
                'batch_id' => $batch->batch_id,
                'batch_code' => $batch->solo,
                'expiry_date' => $batch->ngayhethan,
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
