// /home/ri309/lash-brow-ohana/src/resources/js/lib/api.js

import axios from 'axios';

// --- 1. CSRFトークンの取得 (Laravel/Inertia環境では必須) ---
// <body>タグ内などに設定されているmetaタグからCSRFトークンを取得します。
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// --- 2. Axiosインスタンスの作成と基本設定 ---
const api = axios.create({
    // 全てのAPIリクエストのベースURLを定義
    // LaravelのAPIルート接頭辞に合わせて設定
    baseURL: '/api',

    // クライアントが認証情報（クッキー、Authorizationヘッダーなど）をクロスオリジンリクエストで送信できるようにする
    withCredentials: true,

    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        // Laravel Sanctumなどの認証を使う場合、セッションの整合性確保のためにCSRFトークンをヘッダーに含める
        ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
    },
});

// --- 3. グローバルなインターセプターの設定 (エラーハンドリング/認証) ---

/**
 * レスポンスインターセプター:
 * 共通のエラー処理、特に認証エラー（401 Unauthenticated）を処理します。
 */
api.interceptors.response.use(
    // 成功レスポンスはそのまま通過
    response => response,

    // 失敗レスポンスの処理
    error => {
        // HTTPステータスコードを取得
        const statusCode = error.response?.status;

        // 401 Unauthorized (未認証) の場合
        if (statusCode === 401) {
            console.error("401 Unauthorized: 認証が必要です。ログインページへリダイレクトします。");

            // 実際のアプリケーションでは、ここでInertiaやReact Routerを使ってログインページに遷移させます。
            // 例: window.location.href = '/login'; 
            // または: import { router } from '@inertiajs/react'; router.visit('/login');

        }

        // 403 Forbidden (権限なし) の場合
        else if (statusCode === 403) {
            console.warn("403 Forbidden: この操作を実行する権限がありません。");
        }

        // その他のAPIエラー（4xx, 5xx）は呼び出し元に返す
        return Promise.reject(error);
    }
);

export default api;