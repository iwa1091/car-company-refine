<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>予約キャンセル</title>

    <style>
        :root{
            --bg:#f6f7f8;
            --card:#ffffff;
            --text:#101828;
            --muted:#667085;
            --border:#eaecf0;
            --success:#12b76a;
            --danger:#d92d20;
            --shadow:0 10px 30px rgba(16,24,40,.08);
            --radius:16px;
        }

        body{
            margin:0;
            background:var(--bg);
            color:var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans JP", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
        }

        .wrap{
            max-width: 720px;
            margin: 40px auto;
            padding: 0 16px;
        }

        .card{
            background:var(--card);
            border:1px solid var(--border);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            overflow:hidden;
        }

        .header{
            padding: 18px 20px;
            border-bottom:1px solid var(--border);
        }

        .badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-weight: 900;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .badge--ok{ color: var(--success); }
        .badge--ng{ color: var(--danger); }

        .title{
            margin: 0;
            font-size: 18px;
        }

        .body{
            padding: 18px 20px 20px;
        }

        .message{
            margin: 0;
            font-size: 14px;
            line-height: 1.8;
            color: var(--text);
        }

        .muted{
            margin-top: 10px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .actions{
            margin-top: 16px;
            display:flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn{
            appearance:none;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 800;
            cursor:pointer;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            transition: transform .05s ease, background .2s ease, border-color .2s ease;
            user-select:none;
        }
        .btn:active{ transform: translateY(1px); }

        .btn-primary{
            background:#101828;
            color:#fff;
        }
        .btn-primary:hover{ background:#0b1220; }

        .btn-secondary{
            background:#fff;
            border-color: var(--border);
            color: var(--text);
        }
        .btn-secondary:hover{ background:#f9fafb; }

        @media (max-width: 520px){
            .wrap{ margin: 20px auto; }
            .btn{ width: 100%; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                @if(!empty($ok))
                    <div class="badge badge--ok">✅ 完了</div>
                    <h1 class="title">キャンセルが完了しました</h1>
                @else
                    <div class="badge badge--ng">⚠️ エラー</div>
                    <h1 class="title">キャンセルを実行できませんでした</h1>
                @endif
            </div>

            <div class="body">
                <p class="message">{{ $message }}</p>

                <p class="muted">
                    ご不明点がある場合は、管理者へお問い合わせください。
                </p>

                <div class="actions">
                    <a href="https://refine-keeper.com" class="btn btn-primary">トップへ戻る</a>
                    <a href="/reservation" class="btn btn-secondary">予約ページへ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
