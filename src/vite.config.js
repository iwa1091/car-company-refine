import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    server: {
        host: true,       // サーバーのホストを "localhost" に設定
        port: 5173,       // Vite のデフォルトポート（必要に応じて変更）
        strictPort: true, // ポートの重複時にエラーを発生させる
        hmr: {
            host: 'localhost', // Hot Module Replacement のホスト設定
        },
    },

    resolve: {
        alias: {
            // '@' エイリアスで resources/js ディレクトリを参照
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },

    plugins: [
        laravel({
            input: [
                'resources/css/base/theme.css', // 共通テーマ CSS
                'resources/css/base/global.css', // グローバル CSS
                'resources/css/layout/app-shell.css', // ★追加（app_inertia.blade.php が参照）
                'resources/css/pages/menu_price/menu_price.css', // ★追加（menu_price.blade.php が参照）
                'resources/js/app.jsx',          // メインの JS ファイル（React）
                'resources/css/layout/header.css',
                'resources/css/layout/footer.css',
            ],
            refresh: true, // Blade テンプレートを変更した際に自動更新
        }),
        react(), // React 用の Vite プラグイン
    ],

    build: {
        manifest: true, // Vite のマニフェストファイルを生成
        rollupOptions: {
            input: [
                'resources/js/app.jsx',          // メインの JS ファイル
                'resources/css/base/theme.css',  // theme.css をバンドル
                'resources/css/base/global.css', // global.css をバンドル
                'resources/css/layout/app-shell.css', // ★追加
                'resources/css/pages/menu_price/menu_price.css', // ★追加
                'resources/css/pages/reservation/reservation-form.css',
                'resources/css/pages/auth/authentication.css',
                'resources/css/pages/admin/service-index.css',
                'resources/css/pages/admin/service-form.css',
                'resources/css/pages/admin/reservation-list.css',
                'resources/css/pages/admin/reservation-edit.css',
                'resources/css/pages/admin/dashboard.css',
                'resources/css/pages/admin/category-modal.css',
                'resources/css/pages/admin/business-hours.css',
                'resources/css/pages/admin/login/login.css',
            ],
        },
    },
});
