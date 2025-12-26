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
                'resources/js/app.jsx',          // メインの JS ファイル（React）
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
            ],
        },
    },
});
