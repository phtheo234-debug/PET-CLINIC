<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class RoleMiddleware
{
    /**
     * Cho phép một hoặc nhiều vai trò, phân tách bằng dấu phẩy.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        $allowedRoles = array_filter(array_map('trim', explode(',', $roles)));

        if (!in_array($user->vaitro, $allowedRoles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện chức năng này.',
            ], 403);
        }

        return $next($request);
    }
}
