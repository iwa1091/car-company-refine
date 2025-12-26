const mix = require('laravel-mix');

/*
|--------------------------------------------------------------------------
| Laravel Mix (Production-ready)
|--------------------------------------------------------------------------
| React + TailwindCSS を Laravel public に直接出力
|--------------------------------------------------------------------------
*/

mix
    .setPublicPath('public') // 相対パスに修正
    .js('resources/js/app.jsx', 'public/js') // 出力ディレクトリを明示
    .react()
    .postCss('resources/css/global.css', 'public/css', [
        require('tailwindcss'),
    ])
    .alias({
        // ★★★ 欠けていたエイリアス設定をここに追加 ★★★
        '@': 'resources/js',
    })
    .version();

if (!mix.inProduction()) {
    mix.sourceMaps();
}
