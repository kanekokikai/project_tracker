<?php

namespace App\Http\Controllers;

use App\Models\ChatworkMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'employee_number.required' => '社員番号を入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        $employeeNumber = trim($validated['employee_number']);
        $password = $validated['password'];

        $member = ChatworkMember::query()
            ->where('employee_number', $employeeNumber)
            ->first();

        $expected = (string) ($member->employee_number ?? '');
        $passwordMatches = $member !== null
            && $expected !== ''
            && strlen($expected) === strlen($password)
            && hash_equals($expected, $password);

        if (! $passwordMatches) {
            return response()->json([
                'success' => false,
                'message' => '社員番号かパスワードが違います',
            ], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('app_authenticated', true);
        $request->session()->put('auth_member_id', $member->id);
        $request->session()->put('auth_member_name', $member->member_name);
        $request->session()->put('auth_time', time());

        return response()->json([
            'success' => true,
            'message' => 'ログインしました',
            'member_name' => $member->member_name,
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
