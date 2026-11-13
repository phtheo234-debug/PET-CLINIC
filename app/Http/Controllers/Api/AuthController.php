<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'mat_khau' => 'required|string',
        ]);

        $user = User::where('username', $fields['username'])->first();

        if (!$user || !Hash::check($fields['mat_khau'], $user->mat_khau)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác'
            ], 401);
        }

        if ($user->trangthai !== 'HOAT_DONG') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tài khoản của bạn đã bị khóa hoặc tạm ngưng'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Đăng nhập thành công',
            'data'    => [
                'user_id'         => $user->user_id,
                'username'        => $user->username,
                'ho_ten'          => $user->ho_ten,
                'vaitro'          => $user->vaitro,
                'phai_doimatkhau' => $user->phai_doimatkhau,
                'token'           => $token,
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        // Luôn trả cùng thông báo để không tiết lộ email có đăng ký hay không.
        if ($user && $user->trangthai === 'HOAT_DONG') {
            $code = (string) random_int(100000, 999999);
            Cache::put('password_reset:' . strtolower($user->email), Hash::make($code), now()->addMinute());

            Mail::raw("Mã xác nhận đặt lại mật khẩu Pet Clinic của bạn là: {$code}. Mã có hiệu lực trong 1 phút.", function ($message) use ($user) {
                $message->to($user->email)->subject('Mã xác nhận đặt lại mật khẩu - Pet Clinic');
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Nếu email tồn tại và đang hoạt động, mã xác nhận đã được gửi. Vui lòng kiểm tra hộp thư.',
        ]);
    }

    public function resetForgottenPassword(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'mat_khau_moi' => 'required|string|min:6|confirmed',
        ]);

        $key = 'password_reset:' . strtolower($fields['email']);
        $hashedCode = Cache::get($key);
        $user = User::where('email', $fields['email'])->where('trangthai', 'HOAT_DONG')->first();

        if (!$user || !$hashedCode || !Hash::check($fields['code'], $hashedCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mã xác nhận không đúng hoặc đã hết hạn.',
            ], 422);
        }

        $user->mat_khau = Hash::make($fields['mat_khau_moi']);
        $user->phai_doimatkhau = false;
        $user->save();
        Cache::forget($key);

        // Thu hồi các phiên đăng nhập cũ sau khi đặt lại mật khẩu.
        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập bằng mật khẩu mới.',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $fields = $request->validate([
            'ho_ten' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->ho_ten = $fields['ho_ten'];
        $user->email = $fields['email'];
        $user->phone = $fields['phone'] ?? null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật thông tin cá nhân thành công.',
            'data' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'phone' => $user->phone,
                'vaitro' => $user->vaitro,
                'phai_doimatkhau' => (bool) $user->phai_doimatkhau,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $fields = $request->validate([
            'mat_khau_cu' => 'required|string',
            'mat_khau_moi' => 'required|string|min:6|different:mat_khau_cu|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($fields['mat_khau_cu'], $user->mat_khau)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        $user->mat_khau = Hash::make($fields['mat_khau_moi']);
        $user->phai_doimatkhau = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Đổi mật khẩu thành công.',
            'data' => [
                'phai_doimatkhau' => false,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Đăng xuất thành công'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()
        ]);
    }
}
