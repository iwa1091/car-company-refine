<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メールアドレス確認のご案内</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f1e9;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="max-width:600px; background-color:#ffffff; border-radius:12px;
                          overflow:hidden; box-shadow:0 8px 18px rgba(0,0,0,0.15);">
                {{-- ヘッダー --}}
                <tr>
                    <td style="background-color:#2f4f3e; padding:18px 24px;">
                        <h1 style="margin:0; font-size:20px; color:#fdfaf5;
                                   font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;">
                            {{ $appName ?? 'Lash Brow Ohana' }}
                        </h1>
                        <p style="margin:4px 0 0; font-size:12px; color:#e7dac6;">
                            眉・まつげ専門サロン｜市原市
                        </p>
                    </td>
                </tr>

                {{-- 本文 --}}
                <tr>
                    <td style="padding:24px 24px 16px;
                               font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;
                               color:#333333; font-size:14px; line-height:1.8;">
                        <p style="margin:0 0 12px;">
                            {{ $user->name ?? 'お客様' }} 様
                        </p>

                        <p style="margin:0 0 12px;">
                            この度は「{{ $appName ?? 'Lash Brow Ohana' }}」にご登録いただき、誠にありがとうございます。
                        </p>

                        <p style="margin:0 0 12px;">
                            アカウントを有効化するために、下記のボタンをクリックして
                            メールアドレスの認証を完了させてください。
                        </p>

                        <p style="margin:0 0 12px; font-size:12px; color:#777777;">
                            ※本メール送信から一定時間が経過すると、リンクは無効になります。<br>
                            その場合は、お手数ですが再度ログイン画面から「認証メールを再送」してください。
                        </p>

                        {{-- 認証ボタン --}}
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $verificationUrl }}"
                                       style="display:inline-block; padding:12px 28px;
                                              background-color:#cdaa63; color:#ffffff;
                                              text-decoration:none; border-radius:999px;
                                              font-size:14px; font-weight:bold;">
                                        メールアドレスを認証する
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 12px; font-size:12px; color:#777777;">
                            ※このメールに心当たりがない場合は、このメールは破棄してください。<br>
                            第三者による誤った操作の可能性がありますが、リンクを開かない限り
                            アカウントは有効化されません。
                        </p>
                    </td>
                </tr>

                {{-- フッター --}}
                <tr>
                    <td style="padding:16px 24px 20px; background-color:#faf5ee;
                               font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;
                               font-size:11px; color:#777777;">
                        <p style="margin:0 0 4px;">
                            Lash Brow Ohana（ラッシュブロウ オハナ）
                        </p>
                        <p style="margin:0;">
                            千葉県市原市｜眉・まつげ専門サロン
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
