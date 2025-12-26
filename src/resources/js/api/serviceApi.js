// /home/ri309/lash-brow-ohana/src/resources/js/api/serviceApi.js

import api from '../lib/api'; // ベースとなるAxiosインスタンスをインポート

const ADMIN_BASE_URL = '/admin/services'; // 管理者向けサービスAPIのベースURL

/**
 * サービス関連のAPIクライアント関数群
 * 主に管理者向けのCRUD操作と、一般ユーザー向けの公開情報取得を提供します。
 */

// --- 一般ユーザー向け: アクティブなサービス一覧の取得 ---

/**
 * 有効なサービス（予約可能なメニュー）のリストを取得します。
 * これは一般ユーザーの予約フォームで使用されます。
 * @returns {Promise<Array<Object>>} サービスの配列
 */
export const fetchActiveServices = async () => {
    try {
        // 管理者APIと同じエンドポイントを使用し、Laravel側でアクティブなもののみフィルタリングすることを想定
        const response = await api.get(ADMIN_BASE_URL);
        return response.data;
    } catch (error) {
        console.error("アクティブなサービス一覧の取得に失敗しました:", error);
        throw error;
    }
};

// --- 管理者向け: サービスCRUD操作 ---

/**
 * 全サービス一覧を取得します (管理者用)。
 * @returns {Promise<Array<Object>>} サービスの配列
 */
export const fetchAllServices = async () => {
    try {
        const response = await api.get(ADMIN_BASE_URL);
        return response.data;
    } catch (error) {
        console.error("全サービス一覧の取得に失敗しました:", error);
        throw error;
    }
};

/**
 * 新しいサービスを登録します。
 * @param {Object} serviceData - サービスデータ ({ name, description, duration_minutes, price, ... })
 * @returns {Promise<Object>} 作成されたサービスオブジェクト
 */
export const createService = async (serviceData) => {
    try {
        const response = await api.post(ADMIN_BASE_URL, serviceData);
        return response.data;
    } catch (error) {
        console.error("サービス登録に失敗しました:", error);
        throw error;
    }
};

/**
 * 既存のサービスを更新します。
 * @param {number} serviceId - 更新するサービスのID
 * @param {Object} serviceData - 更新するサービスデータ
 * @returns {Promise<Object>} 更新されたサービスオブジェクト
 */
export const updateService = async (serviceId, serviceData) => {
    try {
        const response = await api.put(`${ADMIN_BASE_URL}/${serviceId}`, serviceData);
        return response.data;
    } catch (error) {
        console.error(`サービスID ${serviceId} の更新に失敗しました:`, error);
        throw error;
    }
};

/**
 * サービスを削除します。
 * @param {number} serviceId - 削除するサービスのID
 * @returns {Promise<void>}
 */
export const deleteService = async (serviceId) => {
    try {
        await api.delete(`${ADMIN_BASE_URL}/${serviceId}`);
    } catch (error) {
        console.error(`サービスID ${serviceId} の削除に失敗しました:`, error);
        throw error;
    }
};
