<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class EmailVerificationPromptController extends Controller
{
    /**
     * メール認証待ちページ（React / Inertia 版）
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        // すでにメール認証済みならホーム（または任意のページ）へリダイレクト
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/?verified=1');
        }

        // 認証前 → React の VerifyEmail.jsx を表示
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status') ?? null,
        ]);
    }
}
