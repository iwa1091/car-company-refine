<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1, viewport-fit=cover" />

    {{-- ページタイトル（Inertia対応） --}}
    <title inertia>{{ config('app.name', 'Lash Brow Ohana') }}</title>

    {{-- Ziggy：React 内で route() を使うために必要 --}}
    @routes

    {{-- Vite（React + 共通CSS + Tailwind） --}}
    @viteReactRefresh
    @vite([
        'resources/css/base/theme.css',
        'resources/css/base/global.css',
        'resources/css/layout/app-shell.css',  {{-- 任意：React 専用のレイアウトCSS --}}
        'resources/js/app.jsx',
    ])

    {{-- Inertia Head --}}
    @inertiaHead
</head>

<body class="bg-[var(--background)] text-[var(--foreground)] antialiased">
    @inertia
</body>
</html>
