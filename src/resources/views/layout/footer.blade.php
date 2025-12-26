<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-grid">

            {{-- Logo & Description --}}
            <div class="footer-logo-section">
                <h3 class="salon-name">LASH&BROW ohana</h3>

                <p class="salon-description">
                    美しいまつげと眉で、お客様の自然な美しさを引き出すアイラッシュサロンです。
                </p>

                {{-- SNS Icons --}}
                <div class="social-links">
                    <a href="https://www.instagram.com/" target="_blank" aria-label="Instagram" class="social-link">
                        <img src="{{ asset('img/icon-instagram.svg') }}" alt="Instagram" class="footer-sns-icon">
                    </a>

                    <a href="https://line.me/R/" target="_blank" aria-label="LINE" class="social-link">
                        <img src="{{ asset('img/icon-line.svg') }}" alt="LINE" class="footer-sns-icon">
                    </a>
                </div>
            </div>

            {{-- Menu --}}
            <div class="footer-menu-section">
                <h4 class="footer-menu-heading">メニュー</h4>
                <nav class="footer-menu-nav">
                    <a href="{{ route('top') }}" class="footer-nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
                    <a href="{{ route('menu_price') }}" class="footer-nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
                    <a href="{{ route('gallery') }}" class="footer-nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
                    <a href="{{ route('online-store.index') }}" class="footer-nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
                    <a href="{{ route('contact.form') }}" class="footer-nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
                </nav>
            </div>

            {{-- Contact Info --}}
            <div class="footer-contact-section">
                <h4 class="footer-contact-heading">お問い合わせ</h4>

                <div class="contact-info-list">

                    <div class="contact-info-item">
                        <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span class="contact-text">0436-63-4724</span>
                    </div>

                    <div class="contact-info-item">
                        <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span class="contact-text">info@lash-brow-ohana.com</span>
                    </div>

                    <div class="contact-info-item address-item">
                        <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="contact-text">
                            〒290-0055<br>
                            千葉県市原市五井東1丁目15-3<br>
                            ブルメリア102
                        </span>
                    </div>

                </div>
            </div>

            {{-- Business Hours --}}
            <div class="footer-hours-section">
                <h4 class="footer-hours-heading">営業時間</h4>

                <div class="hours-list">
                    <div class="hours-item">
                        <span>火曜日〜土曜日</span>
                        <span>09:00〜17:00</span>
                    </div>
                    <div class="hours-item">
                        <span>日曜日・月曜日（祝日等不定休あり）</span>
                        <span>定休日</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Copyright --}}
    <div class="footer-copyright-section">
        <div class="footer-container copyright-container">
            <div class="copyright-content">
                <p>&copy; 2024 lash-brow-ohana. All rights reserved.</p>

                <div class="legal-links">
                    <a href="#" class="legal-link">プライバシーポリシー</a>
                    <a href="#" class="legal-link">利用規約</a>
                </div>
            </div>
        </div>
    </div>
</footer>
