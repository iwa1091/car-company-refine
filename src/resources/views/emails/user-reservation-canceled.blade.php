<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>【REFINE】予約キャンセル完了のお知らせ</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                         "Hiragino Kaku Gothic ProN", Meiryo, Arial, sans-serif;
            background-color: #f6f7f8;
            margin: 0;
            padding: 0;
            color: #101828;
        }
        .container {
            max-width: 680px;
            margin: 28px auto;
            background-color: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #eaecf0;
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
        }
        .header {
            background-color: #101828; /* ユーザー向け：落ち着いた配色 */
            color: #ffffff;
            text-align: center;
            padding: 22px 16px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.6px;
        }
        .content {
            padding: 24px 22px;
            line-height: 1.8;
        }
        .content p {
            margin: 12px 0;
            font-size: 15px;
        }
        .box {
            background: #f9fafb;
            border: 1px solid #eaecf0;
            padding: 14px 16px;
            border-radius: 12px;
            margin: 16px 0;
        }
        .box ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .box li {
            margin: 10px 0;
            font-size: 14px;
        }
        .label {
            color: #667085;
            font-weight: 700;
        }
        .note {
            background: #f2f4f7; /* ユーザー向け：注意色ではなく補足 */
            border: 1px solid #eaecf0;
            color: #344054;
            border-radius: 12px;
            padding: 12px 14px;
            margin-top: 16px;
            font-size: 13px;
            line-height: 1.7;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #667085;
            padding: 18px 12px;
            border-top: 1px solid #eaecf0;
            background: #ffffff;
        }
        @media (max-width: 600px) {
            .container { margin: 10px; }
            .content { padding: 18px 16px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>【REFINE】予約キャンセル完了のお知らせ</h1>
    </div>

    <div class="content">
        <p>{{ $reservation->name }} 様</p>

        <p>
            この度はご連絡ありがとうございます。<br>
            下記のご予約につきまして、キャンセル手続きが完了しました。
        </p>

        <div class="box">
            <ul>
                <li>
                    <span class="label">お名前：</span>
                    {{ $reservation->name }}
                </li>
                <li>
                    <span class="label">メール：</span>
                    {{ $reservation->email }}
                </li>
                <li>
                    <span class="label">電話番号：</span>
                    {{ $reservation->phone ?? '未入力' }}
                </li>
                <li>
                    <span class="label">日時：</span>
                    {{ is_object($reservation->date) ? $reservation->date->format('Y-m-d') : $reservation->date }}
                    {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                </li>
                <li>
                    <span class="label">メニュー：</span>
                    {{ optional($reservation->service)->name ?? '不明' }}
                </li>
                <li>
                    <span class="label">備考：</span>
                    {{ $reservation->notes ?? 'なし' }}
                </li>

                @if(!empty($reservation->cancel_reason))
                    <li>
                        <span class="label">キャンセル理由：</span>
                        {{ $reservation->cancel_reason }}
                    </li>
                @endif
            </ul>
        </div>

        <div class="note">
            ※ ご予約のキャンセルは本メールにて完了となります。<br>
            ※ もしお心当たりがない場合は、お手数ですが店舗までご連絡ください。
        </div>

        <p style="margin-top: 18px;">REFINE</p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} REFINE. All rights reserved.
    </div>
</div>
</body>
</html>
