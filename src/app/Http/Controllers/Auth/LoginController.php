<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Providers\RouteServiceProvider;

class LoginController extends Controller
{
    /**
     * ログインページ表示（Inertia）
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => \Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * ログイン処理
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        // 不要な過去の intended をクリア（/items に飛ぶ問題対策）
        $request->session()->forget('url.intended');

        return redirect(RouteServiceProvider::redirectTo());
    }

    /**
     * ログアウト処理
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
