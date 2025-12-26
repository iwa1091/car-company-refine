@php
    use Illuminate\Support\Carbon;

    $dateText = method_exists($reservation, 'getFormattedDateAttribute')
        ? $reservation->formatted_date
        : Carbon::parse($reservation->date)->format('Y年m月d日');

    $timeText = method_exists($reservation, 'getFormattedTimeAttribute')
        ? $reservation->formatted_time
        : Carbon::parse($reservation->start_time)->format('H:i');
@endphp

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ご来店ありがとうございます</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'ヒラギノ角ゴ ProN W3', 'Noto Sans JP', sans-serif; line-height: 1.7; color: #333;">
    <p>{{ $reservation->name }} 様</p>

    <p>
        先日は「Lash Brow Ohana」へご来店いただき、誠にありがとうございました。
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

    <p>
        ■ ご利用内容<br>
        日時：{{ $dateText }} {{ $timeText }}〜<br>
        メニュー：{{ $reservation->service->name ?? 'ご利用メニュー' }}<br>
        ご予約番号：{{ $reservation->reservation_code ?? '－' }}
    </p>

    @if ($pattern === 'after_3days')
        <p>
            施術後のお肌・眉・まつげの状態はいかがでしょうか。<br>
            気になる点やご不明な点がございましたら、些細なことでもお気軽にご相談ください。
        </p>

        <p>
            今後も{{ $reservation->name }}様の「若々しさと清潔感」を引き出すお手伝いができましたら幸いです。
        </p>
    @elseif ($pattern === 'after_1month')
        <p>
            前回のご来店から、おおよそ<strong>1か月</strong>が経過いたしました。<br>
            そろそろラインの乱れや、眉・まつげのデザインの崩れが気になり始めるタイミングかと思います。
        </p>

        <p>
            当サロンでは、<strong>1か月〜1か月半程度</strong>の周期でのご来店をおすすめしております。<br>
            次回のご予約やメニューのご相談などございましたら、ぜひお気軽にお問い合わせください。
        </p>
    @endif

    <p>
        今後とも「Lash Brow Ohana」をどうぞよろしくお願いいたします。
    </p>

    <p>
        ----------------------------------------<br>
        Lash Brow Ohana（ラッシュブロウ オハナ）<br>
        千葉県市原市　眉・まつげ専門サロン<br>
        ----------------------------------------
    </p>
</body>
</html>
