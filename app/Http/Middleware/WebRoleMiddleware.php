<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class WebRoleMiddleware
{
    /**
     * Chỉ cho phép người dùng thuộc danh sách vai trò mở trang giao diện.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->attributes->get('web_authenticated_user');
        $allowedRoles = array_filter(array_map('trim', explode(',', $roles)));

        if (!$user || !in_array($user->vaitro, $allowedRoles, true)) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
