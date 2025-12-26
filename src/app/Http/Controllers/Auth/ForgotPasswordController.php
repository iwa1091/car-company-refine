<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    /**
     * パスワードリセットメール送信フォーム（React/Inertia）
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * パスワードリセットメール送信処理
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        // パスワードリセットリンク送信
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // 成功時：メールが存在しない場合でも成功として返す（セキュリティ上）
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // エラー時（特殊ケース）
        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}
