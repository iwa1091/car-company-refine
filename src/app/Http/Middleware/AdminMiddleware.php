<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // 【重要】Authファサードをインポートします

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ユーザーが 'admin' ガードで認証されているか確認する
        if (Auth::guard('admin')->check()) {
            
            $user = Auth::guard('admin')->user();
            
            // 認証済みユーザーの role が 'admin' であることを確認
            if ($user && $user->role === 'admin') {
                return $next($request); // 権限があるため続行
            }
        }

        // 認証されていない、または権限がない場合
        // 403エラーではなく、管理者ログイン画面へリダイレクトするのが一般的です。
        // ※ 'admin.login' はあなたが設定した管理者ログインルート名である必要があります
        return redirect()->route('admin.login');
    }
}
