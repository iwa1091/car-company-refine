<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * パスワードリセットメールの送信内容を定義
     *
     * @param  mixed  $notifiable  通知対象のユーザー（Userモデル）
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'Lash-Brow-Ohana');

        // 親クラスのロジックを使ってリセットURLを生成
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('【' . $appName . '】パスワード再設定のご案内')
            ->view('emails.auth.reset-password', [
                'appName' => $appName,
                'user'    => $notifiable,
                'url'     => $resetUrl,
            ]);
    }
}
