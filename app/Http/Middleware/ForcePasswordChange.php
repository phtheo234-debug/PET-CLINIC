<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Chỉ cho phép xem thông tin cá nhân, đổi mật khẩu hoặc đăng xuất
     * khi tài khoản vẫn đang sử dụng mật khẩu tạm thời.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->phai_doimatkhau) {
            $allowedPaths = [
                'api/v1/users/me',
                'api/v1/users/change-password',
                'api/v1/auth/logout',
            ];

            if (!in_array($request->path(), $allowedPaths, true)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                    'message' => 'Bạn phải đổi mật khẩu lần đầu trước khi sử dụng hệ thống.',
                ], 403);
            }
        }

        return $next($request);
    }
}
