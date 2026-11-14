<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('app_authenticated') === true && $request->session()->get('auth_member_id')) {
            return $next($request);
        }

        if ($request->session()->get('app_authenticated') === true) {
            $request->session()->forget(['app_authenticated', 'auth_time']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401);
        }

        return redirect()
            ->route('projects.index')
            ->with('auth_error', 'ログインが必要です');
    }
}
