<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::select('user_id', 'username', 'ho_ten', 'email', 'phone', 'vaitro', 'trangthai', 'phai_doimatkhau', 'ngaytao')->get();
        return response()->json(['status'=> 'success', 'data'=> $users]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'username'=> 'required|string|unique:users,username',
            'ho_ten'=> 'required|string|max:100',
            'email'=> 'required|email|unique:users,email',
            'phone'=> 'nullable|string|max:20',
            'vaitro'=> 'required|in:QUAN_TRI,BAC_SI,NHAN_VIEN',
            'mat_khau_tam'=> 'required|string|min:6',
        ]);

        $user = User::create([
            'username'=> $fields['username'],
            'mat_khau'=> Hash::make($fields['mat_khau_tam']),
            'ho_ten'=> $fields['ho_ten'],
            'email'=> $fields['email'],
            'phone'=> $fields['phone'] ?? null,
            'vaitro'=> $fields['vaitro'],
            'trangthai'=> 'HOAT_DONG',
            'phai_doimatkhau'=> true,
        ]);

        return response()->json([
            'status'=> 'success',
            'message'=> 'Khởi tạo tài khoản nhân viên thành công',
            'data'=> $user
        ], 201);
    }

    public function resetPassword(Request $request, $id)
    {
        $fields = $request->validate(['mat_khau_tam' => 'required|string|min:6']);

        $user = User::findOrFail($id);
        $user->mat_khau = Hash::make($fields['mat_khau_tam']);
        $user->phai_doimatkhau = true;
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Cấp lại mật khẩu tạm thời thành công']);
    }

    public function updateStatus(Request $request, $id)
    {
        $fields = $request->validate(['trangthai' => 'required|in:HOAT_DONG,TAM_NGUNG,KHOA']);

        $user = User::findOrFail($id);
        $user->trangthai = $fields['trangthai'];
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Cập nhật trạng thái tài khoản thành công']);
    }
}