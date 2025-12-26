// /resources/js/Pages/Admin/UserList.jsx
import React from "react";
import { usePage, Link } from "@inertiajs/react";

// ✅ モジュール化した CSS をインポート（パスを修正）
import "../../../css/pages/admin/user-list.css";

export default function UserList() {
    const { customers, filters } = usePage().props;

    return (
        <div className="admin-page-container">
            {/* ← 前のページ（ダッシュボード）へ戻るボタン */}
            <div className="page-header">
                <Link href="/admin/dashboard" className="back-button">
                    前のページに戻る
                </Link>
                <h1 className="page-title">顧客一覧</h1>
            </div>

            {/* 🔍 検索フォーム */}
            <form method="GET" className="search-bar">
                <input
                    type="text"
                    name="search"
                    defaultValue={filters?.search || ""}
                    placeholder="名前・メール・電話番号で検索"
                    className="search-input"
                />
                <button type="submit" className="search-button">
                    検索
                </button>
            </form>

            <div className="table-container">
                <table className="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>メール</th>
                            <th>電話番号</th>
                            <th>予約数</th>
                            <th>購入数</th>
                            <th>総支出</th>
                            <th>最終予約日</th>
                            <th>最終購入日</th>
                            <th>メモ</th>
                        </tr>
                    </thead>
                    <tbody>
                        {customers.data.length > 0 ? (
                            customers.data.map((c) => (
                                <tr key={c.id}>
                                    <td>{c.id}</td>
                                    <td>{c.name}</td>
                                    <td>{c.email}</td>
                                    <td>{c.phone}</td>
                                    <td>{c.total_reservations}</td>
                                    <td>{c.total_purchases}</td>
                                    <td>{c.total_spent}</td>
                                    <td>{c.last_reservation_at}</td>
                                    <td>{c.last_purchase_at}</td>
                                    <td className="memo-cell">
                                        {c.memo || "—"}
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="10" className="text-center">
                                    顧客データがありません。
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* 📄 ページネーション */}
            <div className="pagination">
                {customers.links.map((link, index) => (
                    <button
                        key={index}
                        disabled={!link.url}
                        onClick={() =>
                            link.url && (window.location.href = link.url)
                        }
                        className={`pagination-link ${link.active ? "active" : ""
                            }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}
