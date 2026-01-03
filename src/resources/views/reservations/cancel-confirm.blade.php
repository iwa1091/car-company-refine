<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>予約キャンセル確認</title>

    <style>
        :root{
            --bg:#f6f7f8;
            --card:#ffffff;
            --text:#101828;
            --muted:#667085;
            --border:#eaecf0;
            --danger:#d92d20;
            --danger-hover:#b42318;
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

        .card__header{
            padding: 18px 20px;
            border-bottom:1px solid var(--border);
            background: #fff;
        }

        .title{
            margin:0;
            font-size: 20px;
            letter-spacing:.2px;
        }

        .subtitle{
            margin: 8px 0 0 0;
            color:var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .card__body{
            padding: 18px 20px 20px;
        }

        .summary{
            margin: 0 0 14px 0;
            padding: 14px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fafafa;
        }

        .summary__row{
            display:flex;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed #e6e8ec;
        }
        .summary__row:last-child{ border-bottom:none; }

        .summary__label{
            min-width: 110px;
            color:var(--muted);
            font-size: 13px;
        }
        .summary__value{
            font-size: 14px;
            font-weight: 600;
            flex:1;
            word-break: break-word;
        }

        .form-group{
            margin-top: 14px;
        }
        label{
            display:block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        textarea{
            width:100%;
            box-sizing:border-box;
            border:1px solid var(--border);
            border-radius: 12px;
            padding: 12px 12px;
            font-size: 14px;
            line-height: 1.6;
            outline: none;
            background:#fff;
        }
        textarea:focus{
            border-color: #cfd4dc;
            box-shadow: 0 0 0 4px rgba(16,24,40,.06);
        }

        .hint{
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .error{
            margin-top: 8px;
            color: #b42318;
            font-size: 13px;
            font-weight: 600;
        }

        .actions{
            margin-top: 18px;
            display:flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items:center;
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

        .btn-danger{
            background: var(--danger);
            color:#fff;
        }
        .btn-danger:hover{ background: var(--danger-hover); }

        .btn-secondary{
            background:#fff;
            border-color: var(--border);
            color: var(--text);
        }
        .btn-secondary:hover{ background:#f9fafb; }

        .note{
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #fde2e2;
            background: #fff5f5;
            color: #7a271a;
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 520px){
            .wrap{ margin: 20px auto; }
            .summary__row{ flex-direction: column; gap: 4px; }
            .summary__label{ min-width: auto; }
            .btn{ width: 100%; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="card__header">
                <h1 class="title">予約をキャンセルしますか？</h1>
                <p class="subtitle">
                    以下の予約内容をご確認のうえ、キャンセル理由（任意）を入力して「キャンセルを確定する」を押してください。
                </p>
            </div>

            <div class="card__body">
                <div class="summary" aria-label="予約内容">
                    <div class="summary__row">
                        <div class="summary__label">お名前</div>
                        <div class="summary__value">{{ $reservation->name }}</div>
                    </div>

                    <div class="summary__row">
                        <div class="summary__label">日時</div>
                        <div class="summary__value">
                            {{ is_object($reservation->date) ? $reservation->date->format('Y-m-d') : $reservation->date }}
                            {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                            〜
                            {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                        </div>
                    </div>

                    <div class="summary__row">
                        <div class="summary__label">メニュー</div>
                        <div class="summary__value">
                            {{ optional($reservation->service)->name ?? '未設定' }}
                        </div>
                    </div>

                    <div class="summary__row">
                        <div class="summary__label">備考</div>
                        <div class="summary__value">
                            {{ $reservation->notes ? $reservation->notes : 'なし' }}
                        </div>
                    </div>
                </div>

                <div class="note">
                    ※ 誤操作防止のため、このページを開いただけではキャンセルは確定しません。<br>
                    「キャンセルを確定する」を押すと、予約がキャンセルされ、確認メールが送信されます。
                </div>

                <form method="POST" action="{{ route('reservations.cancel', ['token' => $token]) }}">
                    @csrf

                    <div class="form-group">
                        <label for="cancel_reason">キャンセル理由（任意）</label>
                        <textarea
                            id="cancel_reason"
                            name="cancel_reason"
                            rows="4"
                            maxlength="500"
                            placeholder="例）急な予定が入り来店できなくなったため"
                        >{{ old('cancel_reason') }}</textarea>

                        @error('cancel_reason')
                            <div class="error">{{ $message }}</div>
                        @enderror

                        <div class="hint">
                            最大500文字まで入力できます。入力がなくてもキャンセルできます。
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-danger">
                            キャンセルを確定する
                        </button>

                        <a href="https://refine-keeper.com" class="btn btn-secondary">
                            キャンセルしない（トップへ戻る）
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
