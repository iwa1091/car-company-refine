<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Laravelの標準的な「認証済みならリダイレクトする」ミドルウェアです。
// guestミドルウェアとして利用されます。
class RedirectIfAuthenticated
{
    /**
     * リクエストを処理します。
     * すでに認証済みのユーザーがログインページなどにアクセスした場合、
     * ダッシュボードへリダイレクトします。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            // 指定されたガードで認証済みかチェック
            if (Auth::guard($guard)->check()) {
                
                // ★ 管理者ガード('admin')でログイン済みの場合の処理
                if ($guard === 'admin') {
                    // 管理者ダッシュボードへリダイレクト
                    // 'admin.dashboard' ルート名が定義されている必要があります。
                    return redirect()->route('admin.dashboard'); 
                }

                // その他のガード(webなど)でログイン済みの場合
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
