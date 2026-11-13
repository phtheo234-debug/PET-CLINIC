<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebRoleMiddleware
{
    /**
     * Chỉ cho phép người dùng thuộc danh sách vai trò mở trang giao diện.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Lấy user do middleware web.token xác thực.
        $user = $request->attributes->get('web_authenticated_user');

        // Dự phòng nếu request đã có user thông qua Laravel Auth.
        if (!$user) {
            $user = $request->user() ?? auth()->user();
        }

        if (!$user) {
            abort(401, 'Bạn chưa đăng nhập.');
        }

        // Tách danh sách vai trò được phép.
        $allowedRoles = [];

        foreach ($roles as $role) {
            foreach (explode(',', $role) as $item) {
                $item = strtoupper(trim($item));

                if ($item !== '') {
                    $allowedRoles[] = $item;
                }
            }
        }

        // Lấy vai trò hiện tại.
        $currentRole = strtoupper(trim($user->vaitro ?? ''));

        // Kiểm tra quyền.
        if (!in_array($currentRole, $allowedRoles, true)) {
            abort(403, 'Bạn không có quyền truy cập.');
        }

        return $next($request);
    }
}
