<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Danh sách tài khoản nhân viên.
     */
    public function index()
    {
        $users = User::select(
            'user_id',
            'username',
            'ho_ten',
            'email',
            'phone',
            'vaitro',
            'trangthai',
            'phai_doimatkhau',
            'ngaytao'
        )
            ->where('vaitro', '!=', 'QUAN_TRI')
            ->orderBy('user_id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }


    /**
     * Xem chi tiết tài khoản.
     */
    public function show($id)
    {
        $user = User::select(
            'user_id',
            'username',
            'ho_ten',
            'email',
            'phone',
            'vaitro',
            'trangthai',
            'phai_doimatkhau',
            'ngaytao'
        )->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }


    /**
     * Quản trị viên tạo tài khoản mới.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                'unique:users,username'
            ],

            'ho_ten' => [
                'required',
                'string',
                'max:100'
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                'unique:users,email'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],

            'vaitro' => [
                'required',
                Rule::in([
                    'BAC_SI',
                    'NHAN_VIEN'
                ])
            ],

            'mat_khau_tam' => [
                'required',
                'string',
                'min:6'
            ],
        ]);

        $user = User::create([
            'username' => $fields['username'],
            'mat_khau' => Hash::make($fields['mat_khau_tam']),
            'ho_ten' => $fields['ho_ten'],
            'email' => $fields['email'],
            'phone' => $fields['phone'] ?? null,
            'vaitro' => $fields['vaitro'],
            'trangthai' => 'HOAT_DONG',
            'phai_doimatkhau' => true,
            'ngaytao' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tạo tài khoản thành công.',
            'data' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'phone' => $user->phone,
                'vaitro' => $user->vaitro,
                'trangthai' => $user->trangthai,
            ]
        ], 201);
    }


    /** Cập nhật thông tin tài khoản nhân viên/bác sĩ. */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->vaitro === 'QUAN_TRI' || (int) $user->user_id === (int) $request->user()->user_id) {
            return response()->json(['status' => 'error', 'message' => 'Không thể chỉnh sửa tài khoản quản trị hệ thống hoặc tài khoản đang đăng nhập.'], 403);
        }
        $fields = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->user_id, 'user_id')],
            'ho_ten' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'vaitro' => ['required', Rule::in(['BAC_SI', 'NHAN_VIEN'])],
        ]);
        $user->fill($fields);
        $user->save();
        return response()->json(['status' => 'success', 'message' => 'Cập nhật tài khoản thành công.', 'data' => [
            'user_id' => $user->user_id, 'username' => $user->username, 'ho_ten' => $user->ho_ten,
            'email' => $user->email, 'phone' => $user->phone, 'vaitro' => $user->vaitro, 'trangthai' => $user->trangthai,
        ]]);
    }

    /** Xóa tài khoản nhân viên/bác sĩ. */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->vaitro === 'QUAN_TRI' || (int) $user->user_id === (int) $request->user()->user_id) {
            return response()->json(['status' => 'error', 'message' => 'Không thể xóa tài khoản quản trị hệ thống hoặc tài khoản đang đăng nhập.'], 403);
        }
        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'Không thể xóa tài khoản vì đã phát sinh dữ liệu nghiệp vụ liên quan. Hãy khóa tài khoản thay thế.'], 409);
        }
        return response()->json(['status' => 'success', 'message' => 'Đã xóa tài khoản thành công.']);
    }
    /**
     * Quản trị viên cấp lại mật khẩu.
     */
    public function resetPassword(Request $request, $id)
    {
        $fields = $request->validate([
            'mat_khau_tam' => [
                'required',
                'string',
                'min:6'
            ]
        ]);

        $user = User::findOrFail($id);

        $user->mat_khau = Hash::make($fields['mat_khau_tam']);
        $user->phai_doimatkhau = true;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cấp lại mật khẩu tạm thời thành công.'
        ]);
    }


    /**
     * Cập nhật trạng thái tài khoản.
     */
    public function updateStatus(Request $request, $id)
    {
        $fields = $request->validate([
            'trangthai' => [
                'required',
                Rule::in([
                    'HOAT_DONG',
                    'TAM_NGUNG',
                    'KHOA'
                ])
            ]
        ]);

        $user = User::findOrFail($id);

        // Hệ thống chỉ sử dụng một tài khoản quản trị; không cho khóa tài khoản quản trị.
        if ($user->vaitro === 'QUAN_TRI' && $fields['trangthai'] !== 'HOAT_DONG') {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể khóa hoặc tạm ngưng tài khoản quản trị viên.'
            ], 400);
        }

        // Không cho phép quản trị viên tự khóa hoặc tạm ngưng tài khoản.
        if ((int) $user->user_id === (int) $request->user()->user_id
            && $fields['trangthai'] !== 'HOAT_DONG') {

            return response()->json([
                'status' => 'error',
                'message' => 'Bạn không thể tự khóa hoặc tạm ngưng tài khoản đang đăng nhập.'
            ], 400);
        }

        $user->trangthai = $fields['trangthai'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật trạng thái tài khoản thành công.'
        ]);
    }
}
