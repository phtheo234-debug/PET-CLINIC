<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebRoleMiddleware
{
    /**
     * Chỉ cho phép người dùng có đúng vai trò mở trang giao diện.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->attributes->get('web_authenticated_user');

        if (!$user || $user->vaitro !== $role) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
