<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * ユーザーが認証されていない場合にリダイレクトすべきパスを取得します。
     * * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // JSONリクエストでない場合（通常のWebアクセスの場合）のみリダイレクトします
        if (! $request->expectsJson()) {
            
            // リクエストパスが 'admin/' で始まる、またはルート名が 'admin.' で始まる場合、
            // 管理者ログインページへリダイレクトします。
            // これにより、Laravelがガード名 'admin' をクラスとして解決しようとする問題を回避します。
            if ($request->is('admin/*') || $request->routeIs('admin.*')) {
                // 管理者ログインルート（routes/web.phpなどで定義されている名前）
                return route('admin.login');
            }

            // それ以外（Webガード）の場合は一般ユーザーのログインルートへリダイレクト
            return route('login');
        }

        return null;
    }

    /**
     * リクエストが指定されたガードのいずれかで認証されていることを確認します。
     * これはLaravelのデフォルト実装ですが、念のため含めます。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }
}
