<p>新しい予約が入りました。</p>

<ul>
    <li>お名前：{{ $reservation->name }}</li>
    <li>メール：{{ $reservation->email }}</li>
    <li>日付：{{ $reservation->date }}</li>
    <li>時間：{{ $reservation->start_time }}</li>
    <li>メニュー：{{ $reservation->service->name ?? '不明' }}</li>
    <li>メモ：{{ $reservation->notes ?? 'なし' }}</li>
</ul>
