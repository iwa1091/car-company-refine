<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * 認証メール再送処理
     */
    public function store(Request $request): RedirectResponse
    {
        // すでに認証済みの場合は、認証画面をスキップしてリダイレクト
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home'); // 認証後のリダイレクト先を設定
        }

        // メール認証再送信
        $request->user()->sendEmailVerificationNotification();

        // フラッシュメッセージを追加してリダイレクト
        return back()->with('resent', true); // 再送信されたことを通知
    }
}
