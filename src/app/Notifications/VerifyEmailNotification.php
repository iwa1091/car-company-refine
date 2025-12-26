<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;  // 署名付きURL生成

class VerifyEmailNotification extends Notification
{
    /**
     * 配信されるチャンネル
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * メール通知
     *
     * @param  mixed  $notifiable  通知対象ユーザー（User）
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // 署名付きメール認証URLを生成
        $verificationUrl = URL::signedRoute('verification.verify', [
            'id'   => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        $appName = config('app.name', 'Lash-Brow-Ohana');

        return (new MailMessage)
            ->subject('【' . $appName . '】メールアドレス確認のお願い')
            ->view('emails.auth.verify-email', [
                'appName'         => $appName,
                'user'            => $notifiable,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
