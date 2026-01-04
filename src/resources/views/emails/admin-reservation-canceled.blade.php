<p>【キャンセル通知】新しいキャンセルが発生しました。</p>

<ul>
    <li>お名前：{{ $reservation->name }}</li>
    <li>メール：{{ $reservation->email }}</li>
    <li>日付：{{ $reservation->date }}</li>
    <li>時間：{{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}</li>
    <li>メニュー：{{ $reservation->service->name ?? '不明' }}</li>
    <li>
        備考：
        {{ $reservation->notes !== null && $reservation->notes !== '' 
            ? $reservation->notes 
            : 'なし' }}
    </li>

    <li>
        キャンセル理由：
        {{ $reservation->cancel_reason !== null && $reservation->cancel_reason !== '' 
            ? $reservation->cancel_reason 
            : 'なし' }}
    </li>

</ul>

<p>※この予約はキャンセル扱いとなりました。</p>
<p>ご確認をお願いいたします。</p>
