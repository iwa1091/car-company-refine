@php
    use Illuminate\Support\Carbon;

    // Reservation モデル側のアクセサがあればそちらを優先
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
    <title>ご予約リマインド</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'ヒラギノ角ゴ ProN W3', 'Noto Sans JP', sans-serif; line-height: 1.7; color: #333;">
    <p>{{ $reservation->name }} 様</p>

    <p>
        いつもご利用いただきありがとうございます。<br>
        眉・まつげ専門サロン「Lash Brow Ohana」でございます。
    </p>

    <p>
        ご予約いただいておりますご来店日が、<strong>{{ $daysBefore }}日前</strong>となりましたのでご案内いたします。
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

    <p>
        ■ ご予約内容<br>
        日時：{{ $dateText }} {{ $timeText }}〜<br>
        メニュー：{{ $reservation->service->name ?? 'ご予約メニュー' }}<br>
        ご予約番号：{{ $reservation->reservation_code ?? '－' }}
    </p>

    <p>
        当日は、ご予約時刻の5分前を目安にご来店いただけますとスムーズにご案内が可能です。<br>
        もしご都合が悪くなった場合や、ご予約内容の変更をご希望の際は、お早めにご連絡いただけますと幸いです。
    </p>

    <p>
        それでは、{{ $dateText }}のご来店を心よりお待ちしております。
    </p>

    <p>
        ----------------------------------------<br>
        Lash Brow Ohana（ラッシュブロウ オハナ）<br>
        千葉県市原市　眉・まつげ専門サロン<br>
        ----------------------------------------
    </p>
</body>
</html>
