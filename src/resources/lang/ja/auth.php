<?php
// /resources/lang/ja/auth.php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | These language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    // ログイン失敗（メール/パスワード不一致）
    'failed' => 'メールアドレスまたはパスワードが一致しません。',

    // パスワードが正しくない（通常は failed が使われますが一応用意）
    'password' => 'パスワードが正しくありません。',

    // ログイン試行回数制限（:seconds / :minutes が置換されます）
    'throttle' => 'ログイン試行回数が多すぎます。:seconds秒後に再度お試しください。',
];
