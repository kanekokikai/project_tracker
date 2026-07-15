<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'パスワードを入力してください',
        ]);

        $configuredPassword = config('project_auth.password');

        if ($configuredPassword === '' || $validated['password'] !== $configuredPassword) {
            return response()->json([
                'success' => false,
                'message' => 'パスワードが正しくありません',
            ], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('app_authenticated', true);
        $request->session()->put('auth_time', time());

        return response()->json([
            'success' => true,
            'message' => '認証に成功しました',
            'csrf_token' => csrf_token(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('projects.index');
    }
}
