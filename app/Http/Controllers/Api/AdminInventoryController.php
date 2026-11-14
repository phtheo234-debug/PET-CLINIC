<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminInventoryController extends Controller
{
    private const CATEGORIES = ['THUOC', 'VAC_XIN', 'THUC_AN', 'DO_DUNG'];
    private const BATCH_CATEGORIES = ['THUOC', 'VAC_XIN', 'THUC_AN'];

    public function store(Request $request)
    {
        $fields = $request->validate([
            'masanpham' => ['required', 'string', 'max:50', 'regex:/^(THUOC|VACXIN|THUCAN|DODUNG)-[0-9]{8}-[0-9]{6}$/', 'unique:inventory,masanpham'],
            'ten_sanpham' => 'required|string|max:100',
            'danhmuc' => ['required', Rule::in(self::CATEGORIES)],
            'donvitinh' => 'nullable|string|max:20',
            'dongia' => 'required|numeric|min:0',
            'soluong' => 'required|integer|min:0',
            'ngaynhap' => 'nullable|date|before_or_equal:today',
            'ngaysanxuat' => 'nullable|date|before_or_equal:ngaynhap|before_or_equal:today',
            'ngayhethan' => 'nullable|date|after:ngaysanxuat|after:ngaynhap|after_or_equal:today',
            'solo' => 'nullable|string|max:50|regex:/^LO-[0-9]{8}-[0-9]{6}$/',
        ]);

        if (in_array($fields['danhmuc'], self::BATCH_CATEGORIES, true) && (int) $fields['soluong'] > 0 && (empty($fields['ngaysanxuat']) || empty($fields['ngayhethan']))) {
            return response()->json(['status' => 'error', 'message' => 'Khi thêm số lượng thuốc, vắc-xin hoặc thức ăn, cần nhập ngày sản xuất và hạn sử dụng.'], 422);
        }

        $item = DB::transaction(function () use ($fields) {
            $id = DB::table('inventory')->insertGetId(
                array_merge($this->values($fields), ['masanpham' => $fields['masanpham']])
            );

            if (in_array($fields['danhmuc'], self::BATCH_CATEGORIES, true) && (int) $fields['soluong'] > 0) {
                $lotCode = $fields['solo'] ?? ('LO-' . now()->format('Ymd') . '-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
                if (DB::table('inventory_batches')->where('item_id', $id)->where('solo', $lotCode)->exists()) {
                    throw new \RuntimeException('Mã lô vừa tạo đã tồn tại. Vui lòng thử lưu lại để tạo mã mới.');
                }
                DB::table('inventory_batches')->insert([
                    'item_id' => $id,
                    'solo' => $lotCode,
                    'soluong' => (int) $fields['soluong'],
                    'ngaynhap' => $fields['ngaynhap'] ?? now()->toDateString(),
                    'ngaysanxuat' => $fields['ngaysanxuat'],
                    'ngayhethan' => $fields['ngayhethan'],
                    'trangthai' => 'DANG_SU_DUNG',
                ]);
            }

            return DB::table('inventory')->where('item_id', $id)->first();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Đã thêm mặt hàng vào danh mục kho.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $item = DB::table('inventory')->where('item_id', $id)->first();
        if (!$item || !in_array($item->danhmuc, self::CATEGORIES, true)) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy mặt hàng.'], 404);
        }

        $fields = $request->validate([
            'ten_sanpham' => 'required|string|max:100',
            'danhmuc' => ['required', Rule::in(self::CATEGORIES)],
            'donvitinh' => 'nullable|string|max:20',
            'dongia' => 'required|numeric|min:0',
            'soluong' => 'nullable|integer|min:0',
        ]);

        if ($item->danhmuc === 'DO_DUNG' && $fields['danhmuc'] !== 'DO_DUNG' && (int) $item->soluongton > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể chuyển loại kho khi đồ dùng vẫn còn tồn. Hãy xử lý tồn kho trước.',
            ], 422);
        }

        if ($item->danhmuc !== $fields['danhmuc']
            && DB::table('inventory_batches')->where('item_id', $id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đổi loại kho khi mặt hàng đã có lịch sử nhập lô. Hãy tạo mặt hàng mới để giữ đúng lịch sử tồn kho.',
            ], 422);
        }

        $linkedVaccine = DB::table('vaccination_types')->where('inventory_item_id', $id)->first();
        if ($linkedVaccine && $fields['danhmuc'] !== 'VAC_XIN') {
            return response()->json(['status' => 'error', 'message' => 'Mặt hàng đang liên kết với danh mục tiêm chủng nên không thể đổi khỏi loại Vắc-xin.'], 422);
        }

        if ($item->danhmuc === 'THUOC' && $fields['danhmuc'] !== 'THUOC'
            && DB::table('prescriptions')->where('item_id', $id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đổi danh mục thuốc đã từng được kê đơn.',
            ], 422);
        }

        $values = $this->values($fields);
        if ($fields['danhmuc'] === 'DO_DUNG') {
            $values['soluongton'] = (int) ($fields['soluong'] ?? $item->soluongton);
        } else {
            // Tồn thuốc, vắc-xin và thức ăn được tính từ các lô, không sửa trực tiếp ở màn hình mặt hàng.
            $values['soluongton'] = 0;
        }

        DB::table('inventory')->where('item_id', $id)->update($values);
        if ($linkedVaccine) {
            DB::table('vaccination_types')->where('inventory_item_id', $id)->update([
                'name' => $fields['ten_sanpham'],
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đã cập nhật mặt hàng.',
            'data' => DB::table('inventory')->where('item_id', $id)->first(),
        ]);
    }

    public function destroy(int $id)
    {
        return DB::transaction(function () use ($id) {
            $item = DB::table('inventory')->where('item_id', $id)->lockForUpdate()->first();
            if (!$item || !in_array($item->danhmuc, self::CATEGORIES, true)) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy mặt hàng.'], 404);
            }

            if (DB::table('vaccination_types')->where('inventory_item_id', $id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mặt hàng đang được liên kết với danh mục vắc-xin. Hãy xóa/ngừng loại vắc-xin trong quản lý danh mục trước.',
                ], 422);
            }

            if (DB::table('inventory_batches')->where('item_id', $id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa mặt hàng đã có lô nhập kho. Hãy giữ lại để bảo toàn lịch sử tồn kho.',
                ], 422);
            }

            if ($item->danhmuc === 'DO_DUNG' && (int) $item->soluongton > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa đồ dùng còn tồn kho. Hãy xử lý số lượng tồn trước.',
                ], 422);
            }

            if (DB::table('prescriptions')->where('item_id', $id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa thuốc đã từng được kê đơn để bảo toàn lịch sử khám chữa bệnh.',
                ], 422);
            }

            DB::table('inventory')->where('item_id', $id)->delete();

            return response()->json(['status' => 'success', 'message' => 'Đã xóa mặt hàng.']);
        });
    }

    private function values(array $fields): array
    {
        return [
            'ten_sanpham' => $fields['ten_sanpham'],
            'danhmuc' => $fields['danhmuc'],
            'donvitinh' => $fields['donvitinh'] ?? '',
            'soluongton' => $fields['danhmuc'] === 'DO_DUNG' ? (int) ($fields['soluong'] ?? 0) : 0,
            'dongia' => $fields['dongia'],
            // Không còn sử dụng tồn kho tối thiểu; giữ cột cũ ở mức 0 để tương thích DB.
            'tonkhotoithieu' => 0,
            'lieu_mg_kg' => null,
            'mg_moi_don_vi' => null,
        ];
    }
}
