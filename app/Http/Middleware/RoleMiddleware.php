<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Cho phép một hoặc nhiều vai trò.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Lấy người dùng đã được auth:sanctum xác thực.
        $user = $request->user();

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
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        return $next($request);
    }
}
