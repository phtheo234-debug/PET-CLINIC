
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Kiểm tra quyền truy cập theo vai trò.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        // Kiểm tra người dùng đã đăng nhập
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        // Kiểm tra vai trò người dùng
        if ($user->vaitro !== $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện chức năng này.',
            ], 403);
        }

        return $next($request);
    }
}
