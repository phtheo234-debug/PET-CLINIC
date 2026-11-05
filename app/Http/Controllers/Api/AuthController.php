<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'username'=> 'required|string',
            'mat_khau'=> 'required|string',
        ]);

        $user = User::where('username', $fields['username'])->first();

        if (!$user || !Hash::check($fields['mat_khau'], $user->mat_khau)) {
            return response()->json([
                'status'=> 'error',
                'message'=> 'Tên đăng nhập hoặc mật khẩu không chính xác'
            ], 401);
        }

        if ($user->trangthai !== 'HOAT_DONG') {
            return response()->json([
                'status'=> 'error',
                'message'=> 'Tài khoản của bạn đã bị khóa hoặc tạm ngưng'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'=> 'success',
            'message'=> 'Đăng nhập thành công',
            'data'=> [
                'user_id'=> $user->user_id,
                'username'=> $user->username,
                'ho_ten'=> $user->ho_ten,
                'vaitro'=> $user->vaitro,
                'phai_doimatkhau'=> $user->phai_doimatkhau,
                'token'=> $token,
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $fields = $request->validate([
            'mat_khau_cu'=> 'required|string',
            'mat_khau_moi'=> 'required|string|min:6',
        ]);

        $user = $request->user();

        if (!Hash::check($fields['mat_khau_cu'], $user->mat_khau)) {
            return response()->json([
                'status'=> 'error',
                'message'=> 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        $user->mat_khau = Hash::make($fields['mat_khau_moi']);
        $user->phai_doimatkhau = false;
        $user->save();

        return response()->json([
            'status'=> 'success',
            'message'=> 'Đổi mật khẩu thành công'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'=> 'success',
            'message'=> 'Đăng xuất thành công'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status'=> 'success',
            'data'=> $request->user()
        ]);
    }
}