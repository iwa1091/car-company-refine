<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    */
    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */
    'mailers' => [

        // ---- SMTP（MailHog 用に最適化）----
        'smtp' => [
            'transport'    => 'smtp',
            'scheme'       => env('MAIL_SCHEME'),
            'url'          => env('MAIL_URL'),
            'host'         => env('MAIL_HOST', 'mailhog'),
            'port'         => env('MAIL_PORT', 1025),
            'username'     => env('MAIL_USERNAME'),
            'password'     => env('MAIL_PASSWORD'),
            'timeout'      => null,
            'local_domain' => env(
                'MAIL_EHLO_DOMAIN',
                parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)
            ),
        ],

        // ログ出力：デバッグ用
        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],

        // メールを配列に貯める（テスト用）
        'array' => [
            'transport' => 'array',
        ],

        // フェイルオーバー（SMTP が失敗したら log）
        'failover' => [
            'transport' => 'failover',
            'mailers' => ['smtp', 'log'],
            'retry_after' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Laravel App'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Admin Receiver
    |--------------------------------------------------------------------------
    |
    | 予約通知/キャンセル通知など「管理者宛メール」で使用します。
    | Api/ReservationController.php では config('mail.admin_address') を参照しているため、
    | ここで確実に値が取れるようにします。
    |
    */
    'admin_address' => env('MAIL_ADMIN_ADDRESS'),
    'admin_name' => env('MAIL_ADMIN_NAME', 'Admin'),

    /*
    |--------------------------------------------------------------------------
    | Application Admin Receiver
    |--------------------------------------------------------------------------
    |
    | ContactController（お問い合わせフォーム）が使用します。
    |
    | ex:
    | Mail::to(config('mail.to.address'))->send(...)
    |
    */
    'to' => [
        'address' => env('MAIL_TO_ADDRESS'),
        'name'    => env('MAIL_TO_NAME'),
    ],
];
