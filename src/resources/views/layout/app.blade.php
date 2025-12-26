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

    {{-- 共通CSS（テーマ → グローバル → レイアウト） --}}
    @vite([
        'resources/css/base/theme.css',
        'resources/css/base/global.css',
        'resources/css/layout/header.css',
        'resources/css/layout/footer.css',
    ])

    {{-- ページ専用CSS --}}
    @yield('styles')

    {{-- Alpine.js（モバイルナビ用） --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[var(--background)] text-[var(--foreground)] antialiased">

    @include('layout.header')

    <main>
        @yield('content')
    </main>

    @include('layout.footer')

    @yield('scripts')

</body>
</html>
