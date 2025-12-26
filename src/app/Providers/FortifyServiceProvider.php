<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Fortify の設定をブート処理内で定義
     */
    public function boot()
    {

        // --- ビューの設定 ---
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn () => view('auth.reset-password'));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        // --- ユーザー登録処理をクラスとして指定 ---
        Fortify::createUsersUsing(CreateNewUser::class);

        // --- ログイン処理のカスタマイズ ---
        Fortify::authenticateUsing(function (Request $request) {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => __('ログイン情報が登録されていません'),
                ]);
            }

            return $user;
        });

        // --- ログイン後のリダイレクト先を /items に変更 ---
        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                return redirect('/items');
            }
        });

        // --- 登録後のリダイレクト先を /email/verify に変更 ---
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return redirect('/email/verify');
            }
        });
    }
}
