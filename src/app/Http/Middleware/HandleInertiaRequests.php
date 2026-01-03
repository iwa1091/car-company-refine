<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * 初回 Inertia リクエストで読み込むルートテンプレート
     *
     * @var string
     */
    protected $rootView = 'app_inertia';  // ← ここを変更！

    /**
     * アセットバージョンを判定
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Inertia 全ページに共有するデータ
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user(),
            ],

            /**
             * ✅ flash（セッション）を Inertia props として全ページに共有
             * - CategoryModal / ServiceForm 側で props.flash.category を拾えるようにする
             * - success など一般的なフラッシュも同様に共有しておくと便利
             *
             * ※ fn() で遅延評価にしておくと、必要なときだけセッションを参照しやすく安全
             */
            'flash' => [
                'success'  => fn () => $request->session()->get('success'),
                'message'  => fn () => $request->session()->get('message'),
                'error'    => fn () => $request->session()->get('error'),

                // ★ CategoryController::store() で with('category', $category) した値を拾う
                'category' => fn () => $request->session()->get('category'),
            ],
        ];
    }
}
