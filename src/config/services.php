<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | 各種外部サービスの認証情報を定義します。
    | 例: Stripe, AWS, Postmark, Slackなど。
    |
    */

    // ============================
    // ✉️ メールサービス
    // ============================
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ============================
    // 💬 Slack 通知
    // ============================
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ============================
    // 💳 Stripe 決済設定
    // ============================
    'stripe' => [
        // 公開鍵（JavaScript 側で使用）
        'key' => env('STRIPE_KEY'),

        // シークレット鍵（サーバーサイド用）
        'secret' => env('STRIPE_SECRET_KEY'),

        // Webhook 検証用シークレット
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
