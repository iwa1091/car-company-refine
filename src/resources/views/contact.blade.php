@extends('layout.app')

@section('title', 'ご予約・お問い合わせ')

@section('styles')
    @vite(['resources/css/pages/contact/contact.css'])
@endsection

@section('content')
<div class="contact-page">
    <div class="contact-container">

        <!-- ============================
             ページヘッダー
        ============================ -->
        <div class="page-header">
            <h1>ご予約・お問い合わせ</h1>
            <p>
                眉毛の自己処理はご来店の約2週間前からお控えください。<br>
                理想は1ヶ月ほど手を加えずにご来店いただくのがおすすめです。
            </p>
        </div>

        <div class="contact-layout">

            <!-- ============================
                 お問い合わせフォーム
            ============================ -->
            <section class="form-section">
                <div class="card">

                    {{-- 成功メッセージ --}}
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- エラーメッセージ --}}
                    @if ($errors->any())
                        <div class="alert alert-error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card-header">
                        <h2 class="card-title">ご予約フォーム</h2>
                        <p class="card-subtitle">
                            必要事項をご記入の上、送信してください。
                        </p>
                    </div>

                    <div class="card-content">
                        {{-- ★ Laravel のバリデーションを通すフォーム ★ --}}
                        <form action="{{ route('contact.send') }}" method="POST" class="contact-form">
                            @csrf

                            <!-- 名前 + 電話 -->
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="name">お名前 *</label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        class="input-field @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}"
                                        placeholder="山田 太郎"
                                        required
                                    >
                                    @error('name')
                                        <p class="error-text">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-field">
                                    <label for="phone">電話番号 *</label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="input-field @error('phone') is-invalid @enderror"
                                        value="{{ old('phone') }}"
                                        placeholder="090-1234-5678"
                                        required
                                    >
                                    @error('phone')
                                        <p class="error-text">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- メール -->
                            <div class="form-field">
                                <label for="email">メールアドレス *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="input-field @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    placeholder="example@email.com"
                                    required
                                >
                                @error('email')
                                    <p class="error-text">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 件名 -->
                            <div class="form-field">
                                <label for="subject">件名 *</label>
                                <input
                                    type="text"
                                    id="subject"
                                    name="subject"
                                    class="input-field @error('subject') is-invalid @enderror"
                                    value="{{ old('subject') }}"
                                    placeholder="お問い合わせの件名をご記入ください"
                                    required
                                >
                                @error('subject')
                                    <p class="error-text">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ご要望 -->
                            <div class="form-field">
                                <label for="message">お問い合わせ内容 *</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    class="textarea-field @error('message') is-invalid @enderror"
                                    rows="4"
                                    placeholder="お問い合わせ内容をご記入ください"
                                    required
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="error-text">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 送信ボタン -->
                            <button type="submit" class="submit-button">送信する</button>
                        </form>

                    </div>
                </div>
            </section>

            <!-- ============================
                 サイドバー（スマホ視認性UP）
            ============================ -->
            <aside class="sidebar">

                <!-- --- サロン情報 --- -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">サロン情報</h3>
                    </div>

                    <div class="card-content contact-info">

                        <!-- 住所 -->
                        <div class="contact-item">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     stroke-width="2" stroke="currentColor" fill="none">
                                    <path d="M18 8c0 4.5-6 9-6 9s-6-4.5-6-9a6 6 0 0 1 12 0z"/>
                                    <circle cx="12" cy="8" r="2"/>
                                </svg>
                            </span>
                            <div>
                                <p class="info-label">住所</p>
                                <p class="info-text">
                                    〒290-0055<br>
                                    千葉県市原市五井東1丁目15-3<br>
                                    ブルメリア102
                                </p>
                            </div>
                        </div>

                        <!-- 電話 -->
                        <div class="contact-item">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2.08L16 21.95A19.95 19.95 0 0 1 2.05 6L2.08 4.18A2 2 0 0 1 4.18 2h3a2 2 0 0 1 2 2.18A15.96 15.96 0 0 0 14.5 14.5 15.96 15.96 0 0 0 19.82 19.82a2 2 0 0 1 2.18 2.18z"/>
                                </svg>
                            </span>
                            <div>
                                <p class="info-label">電話番号</p>
                                <p class="info-text">0436-63-4724</p>
                            </div>
                        </div>

                        <!-- 支払い方法 -->
                        <div class="contact-item">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                                    <path d="M22 6L12 13L2 6"/>
                                </svg>
                            </span>
                            <div>
                                <p class="info-label">お支払い方法</p>
                                <p class="info-text">現金 / クレジットカード / PayPay</p>
                            </div>
                        </div>

                        <!-- SNSリンク（スマホ最適化） -->
                        <div class="sns-links">
                            <a href="https://lin.ee/あなたのLINE公式URL" target="_blank" class="sns-button line">
                                <img src="{{ asset('img/icon-line.svg') }}" alt="LINE" class="sns-icon">
                                LINEで予約・相談
                            </a>

                            <a href="https://www.instagram.com/lash_brow_ohana" target="_blank" class="sns-button instagram">
                                <img src="{{ asset('img/icon-instagram.svg') }}" alt="Instagram" class="sns-icon">
                                Instagramを見る
                            </a>
                        </div>
                    </div>
                </div>

                <!-- --- 営業時間 --- -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                            </span>
                            営業時間
                        </h3>
                    </div>

                    <div class="card-content">
                        <div class="hours-list">
                            <div class="hours-item">
                                <span class="day">日曜日・月曜日（祝日等不定休あり）</span>
                                <span class="time">定休日</span>
                            </div>

                            <div class="hours-item">
                                <span class="day">火曜日〜土曜日</span>
                                <span class="time">09:00〜17:00</span>
                            </div>
                        </div>

                        <div class="notice-box">
                            <p>
                                ※ 小さな子どもがおりますため、やむを得ずご予約日時の変更をお願いする場合がございます。<br>
                                柔軟に対応いたしますので、ご理解くださいますようお願いいたします。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- --- お急ぎの方 --- -->
                <div class="quick-contact-card">
                    <div class="quick-contact-content">
                        <h3>お急ぎの方は</h3>
                        <p>お電話でのご予約も承っております</p>
                        <a href="tel:03-1234-5678" class="phone-button">電話で予約</a>
                    </div>
                </div>

            </aside>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", async () => {
    const form = document.getElementById("reservationForm");
    const messageBox = document.getElementById("reservationMessage");
    const serviceSelect = document.getElementById("service");
    const timeSelect = document.getElementById("time");

    /* ---- メニュー一覧をAPIから取得 ---- */
    try {
        const res = await fetch("/api/services");
        if (res.ok) {
            const services = await res.json();
            serviceSelect.innerHTML = '<option value="">メニューを選択してください</option>';

            services.forEach(s => {
                const option = document.createElement("option");
                option.value = s.id;
                option.textContent = `${s.name}（${s.price}円 / ${s.duration_minutes}分）`;
                serviceSelect.appendChild(option);
            });
        }
    } catch (err) {
        console.error("サービス一覧取得失敗:", err);
    }

    /* ---- 仮の時間スロット ---- */
    const timeSlots = [
        "10:00","10:30","11:00","11:30","12:00","12:30",
        "13:00","13:30","14:00","14:30","15:00","15:30",
        "16:00","16:30","17:00","17:30","18:00"
    ];
    timeSelect.innerHTML = '<option value="">時間を選択してください</option>';
    timeSlots.forEach(t => {
        const opt = document.createElement("option");
        opt.value = t;
        opt.textContent = t;
        timeSelect.appendChild(opt);
    });

    /* ---- 送信処理 ---- */
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        messageBox.textContent = "送信中...";

        const payload = {
            name: form.name.value,
            phone: form.phone.value,
            email: form.email.value,
            service_id: form.service.value,
            date: form.date.value,
            start_time: form.time.value,
            notes: form.message.value
        };

        try {
            const res = await fetch("/api/reservations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                messageBox.textContent = "ご予約が完了しました！ありがとうございます。";
                form.reset();
            } else {
                const errorData = await res.json();
                messageBox.textContent = errorData.message || "予約に失敗しました。";
            }
        } catch (err) {
            console.error("通信エラー:", err);
            messageBox.textContent = "⚠️ サーバー通信エラーが発生しました。";
        }
    });
});
</script>
@endsection
