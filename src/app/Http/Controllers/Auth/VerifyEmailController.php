<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\User; // モデルを使用
use Illuminate\Support\Facades\Log; // Logを使用するためにインポート

class VerifyEmailController extends Controller
{
    /**
     * メール認証リンクを検証し、ユーザーを認証済みにマークする。
     *
     * @param \Illuminate\Foundation\Auth\EmailVerificationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Fortifyのデフォルト動作と同様に、リクエストからユーザーを取得
        $user = User::find($request->route('id'));

        // ユーザーが存在しないか、不正なリクエストの場合、エラーとしてリダイレクト
        if (!$user) {
            // エラーメッセージと共にリダイレクト (ここではトップページを想定)
            Log::warning('Invalid email verification link for user: ' . $request->route('id'));
            return redirect('/')->with('error', '認証リンクが無効です。');
        }

        // ログに情報を記録
        Log::info('Email verification started for user: ' . $user->id);

        // 既に認証済みであるかを確認
        if ($user->hasVerifiedEmail()) {
            // 既に認証済みの場合は、そのままリダイレクト (ステータス付きで認証済ページへ)
            Log::info('Email already verified for user: ' . $user->id);
            return redirect(config('app.url') . '/mypage?verified=1');
        }

        // email_verified_at を更新
        if ($user->markEmailAsVerified()) {
            // ログに成功した情報を記録
            Log::info('Email verification successful for user: ' . $user->id);
            
            // データベースの更新に成功した場合、イベントを発火
            event(new Verified($user));
        } else {
            // メール認証が失敗した場合
            Log::warning('Failed to mark email as verified for user: ' . $user->id);
            \Log::info('Request ID: ' . $request->route('id'));
            \Log::info('Request Hash: ' . $request->route('hash'));
            \Log::info('Generated Hash: ' . sha1($user->email));
        }

        // 認証完了後、リダイレクト (ステータス付きで認証済ページへ)
        return redirect(config('app.url') . '/mypage?verified=1');
    }
}
