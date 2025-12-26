import { useState } from 'react';
import { route, getCsrfToken, usePage } from '@/utils/inertiaMocks'; // 分離したモックをインポート

/**
 * 予約の削除処理を管理するためのカスタムフック。
 * 削除状態と、実際に削除リクエストを送信する関数を提供します。
 * * @returns {object} { deletingId, handleDelete }
 */
export function useReservationActions() {
    // 削除状態を管理するためのState
    const [deletingId, setDeletingId] = useState(null);
    const { reservations = [] } = usePage().props;

    /**
     * 予約削除ハンドラ (router.deleteの代わりにfetchを使用)
     * @param {number} id - 削除する予約のID
     */
    const handleDelete = async (id) => {
        // 削除確認のカスタムモーダルを実装することが推奨されますが、
        // 今回は動作確認のため一時的に標準のconfirmを使用します。
        // 実際のアプリケーションではカスタムUIに置き換えてください。
        if (!window.confirm(`予約ID: ${id} の予約を本当に削除してもよろしいですか？`)) {
            return;
        }

        setDeletingId(id);

        try {
            console.log(`予約ID: ${id} の削除を試行します。`);

            // route関数を使用して正しい削除エンドポイントURLを生成
            const deleteUrl = route('admin.reservations.destroy', id);

            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(), // CSRFトークンを送信
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                console.log('削除成功: 予約リストを更新します。');
                // 成功後、ページ全体をリロードして最新の状態を反映させる
                window.location.reload();
            } else {
                // エラー応答を解析
                const errorData = await response.json().catch(() => ({ message: '不明なエラー' }));
                console.error('削除失敗:', errorData);
                // alertの代わりにカスタムメッセージボックスを使用することが強く推奨されます
                alert(`削除に失敗しました: ${response.status} ${response.statusText} - ${errorData.message || ''}`);
            }
        } catch (error) {
            console.error('ネットワークエラー:', error);
            // alertの代わりにカスタムメッセージボックスを使用することが強く推奨されます
            alert('ネットワークエラーにより削除に失敗しました。');
        } finally {
            setDeletingId(null);
        }
    };

    return {
        deletingId,
        handleDelete,
        reservations, // 予約データもフック内で取得し、返すようにすると便利です
    };
}
