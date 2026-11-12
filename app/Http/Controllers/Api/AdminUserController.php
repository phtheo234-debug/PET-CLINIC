<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    // 1. Lấy danh sách nhân viên
    public function index()
    {
        $users = User::select('user_id', 'username', 'ho_ten', 'email', 'phone', 'vaitro', 'trangthai', 'phai_doimatkhau', 'ngaytao')->get();
        return response()->json(['status' => 'success', 'data' => $users]);
    }

    // 2. [THÊM MỚI] Xem chi tiết thông tin 1 nhân viên
    public function show($id)
    {
        $user = User::select('user_id', 'username', 'ho_ten', 'email', 'phone', 'vaitro', 'trangthai', 'phai_doimatkhau', 'ngaytao')
                    ->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $user]);
    }

    // 3. Admin khởi tạo tài khoản mới
    public function store(Request $request)
    {
        $fields = $request->validate([
            'username'     => 'required|string|unique:users,username',
            'ho_ten'       => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'nullable|string|max:20',
            'vaitro'       => 'required|in:QUAN_TRI,BAC_SI,NHAN_VIEN',
            'mat_khau_tam' => 'required|string|min:6',
        ]);

        $user = User::create([
            'username'        => $fields['username'],
            'mat_khau'        => Hash::make($fields['mat_khau_tam']),
            'ho_ten'          => $fields['ho_ten'],
            'email'           => $fields['email'],
            'phone'           => $fields['phone'] ?? null,
            'vaitro'          => $fields['vaitro'],
            'trangthai'       => 'HOAT_DONG',
            'phai_doimatkhau' => true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Khởi tạo tài khoản nhân viên thành công',
            'data'    => $user
        ], 201);
    }

    // 4. Admin cấp lại mật khẩu tạm thời
    public function resetPassword(Request $request, $id)
    {
        $fields = $request->validate(['mat_khau_tam' => 'required|string|min:6']);

        $user = User::findOrFail($id);
        $user->mat_khau = Hash::make($fields['mat_khau_tam']);
        $user->phai_doimatkhau = true;
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Cấp lại mật khẩu tạm thời thành công']);
    }

    // 5. [BỔ SUNG LOGIC] Cập nhật trạng thái tài khoản
    public function updateStatus(Request $request, $id)
    {
        $fields = $request->validate(['trangthai' => 'required|in:HOAT_DONG,TAM_NGUNG,KHOA']);

        $user = User::findOrFail($id);

        // THÊM: Ngăn Admin tự khóa tài khoản của chính mình đang đăng nhập
        if ($user->user_id === $request->user()->user_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bạn không thể tự khóa tài khoản của chính mình!'
            ], 400);
        }

        $user->trangthai = $fields['trangthai'];
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Cập nhật trạng thái tài khoản thành công']);
    }
}