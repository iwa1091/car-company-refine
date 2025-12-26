<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/online-store'; // 一般ユーザー向けのデフォルトに設定を戻すか、そのまま維持

    /**
     * Fortifyによって使用される、ログイン後のリダイレクト先を決定するメソッド。
     * config/fortify.php で 'home' => RouteServiceProvider::class . '::redirectTo' が設定されているため、
     * このメソッドがユーザーのロールに基づいてリダイレクト先を決定します。
     *
     * @return string
     */
    public static function redirectTo(): string
    {
        // ログインユーザーのインスタンスを取得
        $user = Auth::user();

        // ユーザーが存在し、かつ管理者権限を持っているかチェック
        // NOTE: ここではユーザーモデルに is_admin プロパティ（またはメソッド）があることを前提とします。
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            // 管理者の場合は商品管理画面へリダイレクト
            return '/admin/products';
        }

        // それ以外のユーザー（一般ユーザー）はオンラインストアのインデックスへリダイレクト
        return '/online-store';
    }


    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
