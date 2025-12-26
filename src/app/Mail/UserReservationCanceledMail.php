<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約キャンセルのお知らせ</title>
    <style>
        /* ===============================
           ベースレイアウト
        =============================== */
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #f9f9fb;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 640px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }
        .header {
            background-color: #b794f4; /* ブランドカラー（ラベンダー） */
            color: #fff;
            text-align: center;
            padding: 22px 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .content {
            padding: 28px 24px;
            line-height: 1.8;
        }
        .content p {
            margin: 12px 0;
            font-size: 15px;
        }
        ul {
            background: #f7f5ff;
            padding: 15px 20px;
            border-radius: 10px;
            list-style: none;
        }
        li {
            margin-bottom: 8px;
            font-size: 15px;
        }

        /* ===============================
           ボタンスタイル
        =============================== */
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 18px 0;
        }
        .btn {
            display: inline-block;
            background-color: #7e5bef;
            color: #ffffff !important;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background-color: #6b46c1;
        }

        /* ===============================
           フッター
        =============================== */
        .footer {
            text-align: center;
            font-size: 13px;
            color: #888;
            padding: 20px;
            border-top: 1px solid #eee;
        }

        /* ===============================
           スマホ対応
        =============================== */
        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 20px 16px;
            }
            .btn {
                width: 100%;
                box-sizing: border-box;
                padding: 14px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lash Brow Ohana</h1>
        </div>
        <div class="content">
            <p>{{ $reservation->name }} 様</p>

            <p>いつも <strong>Lash Brow Ohana</strong> をご利用いただきありがとうございます。</p>

            <p>以下のご予約は、お客様のご希望によりキャンセルが完了いたしました。</p>

            <ul>
                <li><strong>日時：</strong>{{ $reservation->date }} {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}</li>
                <li><strong>メニュー：</strong>{{ $reservation->service->name ?? '不明' }}</li>
                <li><strong>ご要望：</strong>{{ $reservation->notes ?? 'なし' }}</li>
            </ul>

            <p>ご予約内容の確認や別日のご予約をご希望の場合は、下記ボタンより「予約一覧ページ」をご確認ください。</p>

            <div class="btn-wrapper">
                <a href="{{ url('/mypage/reservations') }}" class="btn">▶ ご予約一覧を開く</a>
            </div>

            <p>またのご利用を心よりお待ちしております。</p>

            <p style="margin-top: 24px;">Lash Brow Ohana</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Lash Brow Ohana. All rights reserved.
        </div>
    </div>
</body>
</html>
