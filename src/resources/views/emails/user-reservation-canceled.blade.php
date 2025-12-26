<p>{{ $reservation->name }} 様</p>

<p>いつも <strong>Lash Brow Ohana</strong> をご利用いただきありがとうございます。</p>

<p>以下のご予約はお客様のご希望によりキャンセルされました。</p>

<ul>
    <li>日時：{{ $reservation->date }} {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}</li>
    <li>メニュー：{{ $reservation->service->name ?? '不明' }}</li>
</ul>

<p>キャンセル内容の確認や、別日のご予約をご希望の場合は、以下のボタンからご確認ください。</p>

<p style="margin:20px 0;">
    <a href="{{ url('/mypage/reservations') }}"
       style="display:inline-block; background-color:#4F46E5; color:#ffffff;
              padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;">
        ▶ ご予約一覧を開く
    </a>
</p>

<p>またのご利用を心よりお待ちしております。</p>
<p>Lash Brow Ohana</p>
