<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Customer; // 顧客管理テーブルと連携
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Auth\Events\Verified;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // バリデーション
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'same:password'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // バリデーションエラーログ出力
        if ($validator->fails()) {
            \Log::error('Validation failed', $validator->errors()->toArray());
        }

        // バリデーションが失敗した場合、例外がスローされます
        $validator->validate();

        // ユーザー作成
        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => Hash::make($input['password']),
            'phone'    => $input['phone'] ?? null,
        ]);

        // ユーザー作成ログ出力
        \Log::info('User created:', ['user' => $user]);

        // ★ 顧客テーブル（customers）と同期
        //   - メールアドレスをキーに作成 or 更新
        //   - ReservationController 側の Customer::updateOrCreate と整合性を保つ
        Customer::updateOrCreate(
            ['email' => $user->email],
            [
                'name'  => $user->name,
                'phone' => $user->phone,
            ]
        );

        // メール認証通知を送信（既存の機能を維持）
        $user->sendEmailVerificationNotification();

        // メール送信ログ
        \Log::info('Email verification notification sent to user', ['user_id' => $user->id]);

        return $user;
    }
}
