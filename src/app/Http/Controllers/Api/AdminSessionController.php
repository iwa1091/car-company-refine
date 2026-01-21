<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSessionController extends Controller
{
    // GET /api/csrf
    public function csrf(Request $request)
    {
        // webミドルウェア配下なのでセッション確立される
        return response()->json(['token' => csrf_token()]);
    }

    // GET /api/admin/me
    public function me(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
        ]);
    }

    // POST /api/admin/session
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!Auth::guard('admin')->attempt($validated, true)) {
            return response()->json(['message' => 'メールアドレスまたはパスワードが違います'], 422);
        }

        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    // DELETE /api/admin/session
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
