<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurgeryController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => DB::table('surgeries')->orderBy('ten_phauthuat')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'ma_phauthuat' => 'required|string|max:30|unique:surgeries,ma_phauthuat',
            'ten_phauthuat' => 'required|string|max:100',
            'gia' => 'required|integer|min:0',
            'trangthai' => 'nullable|boolean',
        ]);

        $id = DB::table('surgeries')->insertGetId([
            'ma_phauthuat' => strtoupper(trim($fields['ma_phauthuat'])),
            'ten_phauthuat' => trim($fields['ten_phauthuat']),
            'gia' => $fields['gia'],
            'trangthai' => $fields['trangthai'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'data' => DB::table('surgeries')->where('surgery_id', $id)->first()], 201);
    }

    public function update(Request $request, int $id)
    {
        $fields = $request->validate([
            'ma_phauthuat' => ['required', 'string', 'max:30', Rule::unique('surgeries', 'ma_phauthuat')->ignore($id, 'surgery_id')],
            'ten_phauthuat' => 'required|string|max:100',
            'gia' => 'required|integer|min:0',
            'trangthai' => 'required|boolean',
        ]);

        DB::table('surgeries')->where('surgery_id', $id)->update([
            'ma_phauthuat' => strtoupper(trim($fields['ma_phauthuat'])),
            'ten_phauthuat' => trim($fields['ten_phauthuat']),
            'gia' => $fields['gia'],
            'trangthai' => $fields['trangthai'],
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'data' => DB::table('surgeries')->where('surgery_id', $id)->first()]);
    }

    public function destroy(int $id)
    {
        $used = DB::table('medical_records')->where('surgery_id', $id)->exists();
        if ($used) {
            DB::table('surgeries')->where('surgery_id', $id)->update(['trangthai' => false, 'updated_at' => now()]);
            return response()->json(['status' => 'success', 'message' => 'Loại phẫu thuật đã được sử dụng nên được ngừng áp dụng, không xóa lịch sử.']);
        }

        DB::table('surgeries')->where('surgery_id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Đã xóa loại phẫu thuật.']);
    }
}