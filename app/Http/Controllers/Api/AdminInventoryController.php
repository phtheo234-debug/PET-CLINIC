<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminInventoryController extends Controller
{
    private const CATEGORIES = ['THUOC', 'VAC_XIN', 'THUC_AN', 'DO_DUNG'];

    public function store(Request $request)
    {
        $fields = $request->validate([
            'ten_sanpham' => 'required|string|max:100',
            'danhmuc' => ['required', Rule::in(self::CATEGORIES)],
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
            'tonkhotoithieu' => 'nullable|integer|min:0',
            'lieu_mg_kg' => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
            'mg_moi_don_vi' => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
        ]);

        $item = DB::transaction(function () use ($fields) {
            $temporaryCode = 'TMP' . Str::upper(Str::random(16));
            $id = DB::table('inventory')->insertGetId(
                array_merge($this->values($fields), ['masanpham' => $temporaryCode])
            );
            $code = $this->generatedCode($fields['danhmuc'], $id);
            DB::table('inventory')->where('item_id', $id)->update(['masanpham' => $code]);

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
            'donvitinh' => 'required|string|max:20',
            'dongia' => 'required|numeric|min:0',
            'tonkhotoithieu' => 'nullable|integer|min:0',
            'lieu_mg_kg' => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
            'mg_moi_don_vi' => 'required_if:danhmuc,THUOC|nullable|numeric|gt:0',
        ]);

        if ($item->danhmuc === 'THUOC' && $fields['danhmuc'] !== 'THUOC'
            && DB::table('prescriptions')->where('item_id', $id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đổi danh mục thuốc đã từng được kê đơn.',
            ], 422);
        }

        DB::table('inventory')->where('item_id', $id)->update($this->values($fields));

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

            if (DB::table('inventory_batches')->where('item_id', $id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa mặt hàng đã có lô nhập kho. Hãy giữ lại để bảo toàn lịch sử tồn kho.',
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

    private function generatedCode(string $category, int $id): string
    {
        $prefixes = [
            'THUOC' => 'THUOC',
            'VAC_XIN' => 'VACXIN',
            'THUC_AN' => 'THUCAN',
            'DO_DUNG' => 'DODUNG',
        ];
        $prefix = $prefixes[$category] ?? 'SP';
        $code = $prefix . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

        while (DB::table('inventory')->where('masanpham', $code)->exists()) {
            $code = $prefix . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . Str::upper(Str::random(4));
        }

        return $code;
    }

    private function values(array $fields): array
    {
        return [
            'ten_sanpham' => $fields['ten_sanpham'],
            'danhmuc' => $fields['danhmuc'],
            'donvitinh' => $fields['donvitinh'],
            'dongia' => $fields['dongia'],
            'tonkhotoithieu' => $fields['tonkhotoithieu'] ?? 10,
            'lieu_mg_kg' => $fields['danhmuc'] === 'THUOC' ? ($fields['lieu_mg_kg'] ?? null) : null,
            'mg_moi_don_vi' => $fields['danhmuc'] === 'THUOC' ? ($fields['mg_moi_don_vi'] ?? null) : null,
        ];
    }
}
