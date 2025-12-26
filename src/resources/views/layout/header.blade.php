{{-- Alpine.js で open を管理 --}}
<div x-data="{ open: false }">

    <header class="main-header">
        <div class="header-container">
            <div class="header-content">

                {{-- ロゴ --}}
                <a href="{{ route('top') }}" class="header-logo-link">
                    <span class="site-title">LASH&BROW ohana</span>
                </a>

                {{-- PC：ナビ + SNS アイコングループ --}}
                <div class="desktop-right-group">

                    {{-- デスクトップナビ --}}
                    <nav class="desktop-nav" aria-label="メインメニュー">
                        <a href="{{ route('top') }}" class="nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
                        <a href="{{ route('menu_price') }}" class="nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
                        <a href="{{ route('gallery') }}" class="nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
                        <a href="{{ route('online-store.index') }}" class="nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
                        <a href="{{ route('contact.form') }}" class="nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
                        <a href="{{ route('mypage.index') }}" class="nav-link {{ request()->routeIs('mypage.*') ? 'is-active' : '' }}">マイページ</a>
                    </nav>

                    {{-- ◆ デスクトップ SNS（ナビ右側） --}}
                    <div class="desktop-sns">
                        <a href="https://www.instagram.com/" target="_blank" class="sns-icon-link" aria-label="Instagram">
                            <img src="{{ asset('img/icon-instagram.svg') }}" class="sns-icon" alt="Instagram">
                        </a>
                        <a href="https://line.me/R/" target="_blank" class="sns-icon-link" aria-label="LINE">
                            <img src="{{ asset('img/icon-line.svg') }}" class="sns-icon" alt="LINE">
                        </a>
                    </div>

                </div>

                {{-- モバイルメニューボタン --}}
                <button @click="open = true" class="menu-toggle-button mobile-only" type="button" aria-label="メニューを開く">
                    <svg class="icon-menu" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

            </div>
        </div>
    </header>

    {{-- ================================
          モバイルメニュー Overlay
       =================================== --}}
    <div x-show="open" x-transition.opacity @click.self="open = false" class="mobile-menu-overlay" x-cloak>

        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="mobile-menu"
        >

            <div class="mobile-menu-header">
                <button @click="open = false" class="menu-close-button" type="button" aria-label="メニューを閉じる">
                    <svg class="icon-close" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- モバイル SNS アイコン --}}
            <div class="mobile-sns">
                <a href="https://www.instagram.com/" target="_blank" class="sns-icon-link">
                    <img src="{{ asset('img/icon-instagram.svg') }}" class="sns-icon" alt="Instagram">
                </a>
                <a href="https://line.me/R/" target="_blank" class="sns-icon-link">
                    <img src="{{ asset('img/icon-line.svg') }}" class="sns-icon" alt="LINE">
                </a>
            </div>

            {{-- ロゴ --}}
            <div class="mobile-logo-wrapper">
                <img src="{{ asset('img/logo.jpg') }}" alt="lash-brow-ohana ロゴ" class="mobile-logo">
                <span class="site-title-mobile">lash-brow-ohana</span>
            </div>

            {{-- モバイルメニュー --}}
            <div class="mobile-nav-list">
                <a href="{{ route('top') }}" class="mobile-nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
                <a href="{{ route('menu_price') }}" class="mobile-nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
                <a href="{{ route('gallery') }}" class="mobile-nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
                <a href="{{ route('online-store.index') }}" class="mobile-nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
                <a href="{{ route('contact.form') }}" class="mobile-nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
            </div>

        </div>
    </div>

</div>
