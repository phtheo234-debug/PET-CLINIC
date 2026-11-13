<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class WebTokenAuth
{
    /**
     * Bảo vệ các trang Blade bằng Sanctum token được đồng bộ vào cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = urldecode((string) $request->cookie('petclinic_token', ''));

        if ($plainTextToken === '') {
            return redirect()->route('login');
        }

        $accessToken = PersonalAccessToken::findToken($plainTextToken);

        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return $this->reject($request);
        }

        $user = $accessToken->tokenable;

        if (!$user instanceof User || $user->trangthai !== 'HOAT_DONG') {
            return $this->reject($request);
        }

        if ($user->phai_doimatkhau && !$request->is('change-password')) {
            return redirect()->route('change-password');
        }

        if (!$user->phai_doimatkhau && $request->is('change-password')) {
            return redirect()->route('dashboard');
        }

        $request->attributes->set('web_authenticated_user', $user);

        return $next($request);
    }

    private function reject(Request $request): Response
    {
        return redirect()->route('login')
            ->withCookie(cookie()->forget('petclinic_token'));
    }
}
