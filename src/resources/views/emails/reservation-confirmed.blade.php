<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ご予約完了のお知らせ</title>
    <style>
        /* =========================================
           Email-safe base
        ========================================= */
        body {
            margin: 0;
            padding: 0;
            background: #f4f6fb;
            color: #0b1020;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans JP",
                         "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, Arial, sans-serif;
        }

        a { color: #2b7cff; text-decoration: none; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse; }
        .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; overflow: hidden; mso-hide: all; }

        /* =========================================
           Layout
        ========================================= */
        .wrap {
            width: 100%;
            background: #f4f6fb;
            padding: 28px 0;
        }

        .container {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(10, 20, 40, 0.10);
        }

        /* =========================================
           Header
        ========================================= */
        .header {
            background: #060a12;
            background-image:
                radial-gradient(900px 520px at 15% 0%, rgba(43, 124, 255, 0.30), transparent 60%),
                radial-gradient(800px 460px at 90% 10%, rgba(0, 194, 255, 0.18), transparent 65%),
                linear-gradient(90deg, #0b1020, #060a12);
            padding: 22px 22px;
            text-align: left;
        }

        .brand {
            color: #eaf1ff;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin: 0;
        }

        .header-sub {
            color: rgba(234, 241, 255, 0.72);
            font-size: 12px;
            margin: 6px 0 0 0;
        }

        /* =========================================
           Content
        ========================================= */
        .content {
            padding: 26px 22px 8px;
            line-height: 1.75;
        }

        .greet {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 700;
            color: #0b1020;
        }

        .lead {
            margin: 0 0 14px;
            font-size: 14px;
            color: #1a2540;
        }

        .section-title {
            margin: 18px 0 10px;
            font-size: 14px;
            font-weight: 800;
            color: #0b1020;
        }

        /* =========================================
           Reservation details table
        ========================================= */
        .details {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: #0b1020;
            margin: 10px 0 16px;
        }

        .details td {
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.6;
            vertical-align: top;
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        }

        .details tr:last-child td {
            border-bottom: none;
        }

        .details .k {
            width: 34%;
            color: rgba(234, 241, 255, 0.72);
            white-space: nowrap;
        }

        .details .v {
            color: #eaf1ff;
        }

        /* =========================================
           Note box
        ========================================= */
        .note {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f2f7ff;
            border: 1px solid rgba(43, 124, 255, 0.18);
        }

        .note p {
            margin: 0 0 8px;
            font-size: 13px;
            color: rgba(11, 16, 32, 0.85);
        }

        .note p:last-child { margin-bottom: 0; }

        /* =========================================
           Action button (email safe)
        ========================================= */
        .btn-wrap {
            text-align: center;
            margin: 14px 0 10px;
        }
        .btn-table {
            margin: 0 auto;
        }
        .btn {
            display: inline-block;
            background-color: #d92d20;
            color: #ffffff !important;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 800;
            border-radius: 10px;
            text-decoration: none;
        }
        .btn-sub {
            margin-top: 8px;
            font-size: 12px;
            color: rgba(11, 16, 32, 0.65);
            line-height: 1.6;
        }

        /* =========================================
           Footer
        ========================================= */
        .footer {
            padding: 16px 22px 22px;
            border-top: 1px solid rgba(11, 16, 32, 0.08);
            color: rgba(11, 16, 32, 0.60);
            font-size: 12px;
            line-height: 1.6;
        }

        .footer strong {
            color: rgba(11, 16, 32, 0.78);
        }

        /* =========================================
           Responsive
        ========================================= */
        @media (max-width: 600px) {
            .wrap { padding: 14px 0; }
            .content { padding: 22px 16px 8px; }
            .header { padding: 20px 16px; }
            .footer { padding: 14px 16px 18px; }
            .details .k { width: 38%; }
            .btn { width: 100%; box-sizing: border-box; text-align: center; }
        }
    </style>
</head>

<body>
    {{-- Preheader（受信箱のプレビュー文） --}}
    <div class="preheader">
        ご予約を承りました。日時やメニューなどの内容をご確認ください。
    </div>

    <div class="wrap">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center" style="padding: 0 10px;">
                    <table role="presentation" class="container" width="100%" cellpadding="0" cellspacing="0" border="0">
                        {{-- Header --}}
                        <tr>
                            <td class="header">
                                <p class="brand">REFINE</p>
                                <p class="header-sub">ご予約完了のお知らせ</p>
                            </td>
                        </tr>

                        {{-- Content --}}
                        <tr>
                            <td class="content">
                                <p class="greet">{{ $reservation->name ?? 'お客様' }} 様</p>

                                <p class="lead">
                                    この度は <strong>REFINE</strong> にご予約いただき、誠にありがとうございます。<br>
                                    以下の内容でご予約を承りました。
                                </p>

                                <p class="section-title">ご予約内容</p>

                                @php
                                    $dateText = null;
                                    try {
                                        $dateText = \Carbon\Carbon::parse($reservation->date)->format('Y年n月j日（D）');
                                    } catch (\Exception $e) {
                                        $dateText = $reservation->date;
                                    }

                                    $startText = null;
                                    $endText = null;
                                    try { $startText = \Carbon\Carbon::parse($reservation->start_time)->format('H:i'); } catch (\Exception $e) { $startText = $reservation->start_time; }
                                    try { $endText   = \Carbon\Carbon::parse($reservation->end_time)->format('H:i'); } catch (\Exception $e) { $endText = $reservation->end_time; }

                                    $serviceName = optional($reservation->service)->name ?? '未設定';
                                    $notes = $reservation->notes ?? null;

                                    /**
                                     * ✅ キャンセルURLの不一致対策
                                     * - 旧: /reservations/cancel/{token}
                                     * - 新(Nextページ): /reservation/cancel/{token}
                                     *
                                     * 他ファイルに依存しないよう、$cancelUrl から token を抜き取り、
                                     * フロント(Next)用URLをこのBlade内で組み立てる。
                                     */
                                    $cancelButtonUrl = null;

                                    if (!empty($cancelUrl) && is_string($cancelUrl)) {
                                        $token = null;

                                        // cancelUrl が「トークンのみ」で渡ってくるケースにも対応
                                        if (preg_match('/^[A-Za-z0-9]{20,}$/', $cancelUrl)) {
                                            $token = $cancelUrl;
                                        } else {
                                            $path = parse_url($cancelUrl, PHP_URL_PATH);
                                            if (is_string($path) && $path !== '') {
                                                $seg = trim($path, '/');
                                                $parts = explode('/', $seg);
                                                $token = end($parts) ?: null;
                                            }
                                        }

                                        // フロントURL（定義があれば優先、なければ app.url）
                                        $frontendBase = config('app.frontend_url')
                                            ?? config('services.frontend.url')
                                            ?? config('app.url');

                                        if (!empty($token) && is_string($frontendBase) && $frontendBase !== '') {
                                            // NextのページURLに寄せる
                                            $cancelButtonUrl = rtrim($frontendBase, '/') . '/reservation/cancel/' . $token;
                                        } else {
                                            // 最低限の置換フォールバック（旧URLを新URLへ寄せる）
                                            $cancelButtonUrl = str_replace('/reservations/cancel/', '/reservation/cancel/', $cancelUrl);
                                        }
                                    }
                                @endphp

                                <table role="presentation" class="details" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td class="k"><strong>日時</strong></td>
                                        <td class="v">
                                            {{ $dateText }}
                                            @if($startText)
                                                {{ $startText }}
                                            @endif
                                            @if($endText)
                                                〜 {{ $endText }}
                                            @endif
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="k"><strong>メニュー</strong></td>
                                        <td class="v">{{ $serviceName }}</td>
                                    </tr>

                                    <tr>
                                        <td class="k"><strong>ご要望</strong></td>
                                        <td class="v">{{ $notes ? $notes : 'なし' }}</td>
                                    </tr>
                                </table>

                                <div class="note">
                                    <p><strong>ご来店について</strong></p>
                                    <p>・ご来店はご予約時間ちょうどを目安にお越しください。</p>
                                    <p>・5分以上前のご来店はご遠慮いただいております。</p>
                                </div>

                                {{-- ✅ キャンセル導線（cancelUrl があるときだけ表示） --}}
                                @if(!empty($cancelButtonUrl))
                                    <p class="section-title" style="margin-top: 14px;">キャンセルをご希望の場合</p>
                                    <div class="btn-wrap">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn-table">
                                            <tr>
                                                <td bgcolor="#d92d20" style="border-radius: 10px;">
                                                    <a href="{{ $cancelButtonUrl }}" class="btn">キャンセル手続きへ進む</a>
                                                </td>
                                            </tr>
                                        </table>

                                        <div class="btn-sub">
                                            ※ 上記リンクからキャンセル画面へ進み、確定してください。<br>
                                            <span style="word-break: break-all; display:inline-block;">
                                                {{ $cancelButtonUrl }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <p class="lead" style="margin-top: 0; margin-bottom: 6px;">
                                    ご来店を心よりお待ちしております。
                                </p>
                                <p class="lead" style="margin: 0;">
                                    <strong>REFINE</strong>
                                </p>
                            </td>
                        </tr>

                        {{-- Footer --}}
                        <tr>
                            <td class="footer">
                                <div style="margin-bottom: 8px;">
                                    <strong>本メールは送信専用です。</strong> 返信いただいてもお答えできません。<br>
                                    内容に心当たりがない場合は、お手数ですが破棄してください。
                                </div>
                                <div>
                                    &copy; {{ date('Y') }} REFINE. All rights reserved.
                                </div>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
