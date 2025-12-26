<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Actions\Fortify\CreateNewUser; // CreateNewUserをインポート
use Illuminate\Auth\Events\Verified;

class RegisterController extends Controller
{
    /**
     * 会員登録画面（Inertia）
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * 会員登録処理
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // ユーザー作成（CreateNewUserを使用）
        $createNewUser = new CreateNewUser();
        $user = $createNewUser->create($request->all());

        // 自動でログイン
        Auth::login($user);

        // メール認証画面へリダイレクト
        return redirect()->route('verification.notice');
    }
}
