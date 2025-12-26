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
        ];
    }
}
