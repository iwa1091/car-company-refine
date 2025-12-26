<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use App\Notifications\ResetPasswordNotification; // ★ 追加：パスワードリセット用
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    // 管理者判定メソッド
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * メール認証通知を送信
     * メール認証が未完了の場合にのみ通知を送信する
     */
    public function sendEmailVerificationNotification(): void
    {
        // すでに認証済みであれば、再通知しない
        if ($this->hasVerifiedEmail()) {
            return;
        }

        // 認証未完了の場合、通知を送信
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * パスワードリセット通知を送信
     *
     * ForgotPasswordController → Password::sendResetLink()
     * から内部的に呼び出されるメソッドです。
     * ここで、独自の ResetPasswordNotification を使用します。
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
