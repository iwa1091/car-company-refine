<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Lash Brow Ohana')</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" />

    @php
        // ✅ このページだけヘッダー/フッターを消したい場合に使うフラグ
        // 各Bladeで @section('no_header', true) / @section('no_footer', true) を指定する
        $noHeader = (bool) trim((string)($__env->yieldContent('no_header')));
        $noFooter = (bool) trim((string)($__env->yieldContent('no_footer')));
    @endphp

    {{-- 共通CSS（テーマ → グローバル → レイアウト） --}}
    @vite([
        'resources/css/base/theme.css',
        'resources/css/base/global.css',
        // ヘッダー/フッターを使うページだけ読み込む（余計なCSSを減らす）
        ...($noHeader ? [] : ['resources/css/layout/header.css']),
        ...($noFooter ? [] : ['resources/css/layout/footer.css']),
    ])

    {{-- ページ専用CSS --}}
    @yield('styles')

    {{-- Alpine.js（モバイルナビ用） --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[var(--background)] text-[var(--foreground)] antialiased">

    {{-- ✅ ヘッダー（必要なページだけ非表示にできる） --}}
    @unless($noHeader)
        @include('layout.header')
    @endunless

    <main>
        @yield('content')
    </main>

    {{-- ✅ フッター（必要なページだけ非表示にできる） --}}
    @unless($noFooter)
        @include('layout.footer')
    @endunless

    @yield('scripts')

</body>
</html>
