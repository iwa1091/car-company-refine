// /resources/js/Pages/Admin/ServiceIndex.jsx
import React, { useState } from "react";
import { usePage, Link } from "@inertiajs/react";
import { Inertia } from "@inertiajs/inertia";
import { route } from "ziggy-js";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/service-index.css";

export default function ServiceIndex() {
    const { services: initialServices, categories } = usePage().props;
    const [filterCategory, setFilterCategory] = useState("");
    const [services, setServices] = useState(initialServices);

    const handleDelete = (id) => {
        if (confirm("本当に削除しますか？")) {
            Inertia.delete(route("admin.services.destroy", id), {
                preserveScroll: true,
                onSuccess: () => {
                    setServices(services.filter((s) => s.id !== id));
                },
            });
        }
    };

    const toggleActive = (serviceId) => {
        Inertia.patch(
            route("admin.services.toggle", serviceId),
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const updatedService = page.props.services.find(
                        (s) => s.id === serviceId
                    );
                    setServices(
                        services.map((s) =>
                            s.id === serviceId ? updatedService : s
                        )
                    );
                },
            }
        );
    };

    const filteredServices = filterCategory
        ? services.filter(
            (s) => s.category_id === parseInt(filterCategory, 10)
        )
        : services;

    return (
        <div className="admin-service-page">
            <div className="admin-service-container">
                {/* 🔙 ダッシュボードへ戻るボタン */}
                <div className="service-back-area">
                    <Link
                        href={route("admin.dashboard")}
                        className="service-back-button"
                    >
                        前のページに戻る
                    </Link>
                </div>

                {/* ヘッダー（タイトル + 新規作成） */}
                <div className="service-page-header">
                    <h1 className="service-page-title">サービス一覧</h1>
                    <Link
                        href={route("admin.services.create")}
                        className="service-create-button"
                    >
                        新規作成
                    </Link>
                </div>

                {/* カテゴリフィルタ */}
                <div className="service-filter">
                    <label className="service-filter-label">
                        カテゴリで絞り込み:
                    </label>
                    <select
                        value={filterCategory}
                        onChange={(e) => setFilterCategory(e.target.value)}
                        className="service-filter-select"
                    >
                        <option value="">すべて</option>
                        {categories.map((cat) => (
                            <option key={cat.id} value={cat.id}>
                                {cat.name}
                            </option>
                        ))}
                    </select>
                </div>

                {/* テーブル */}
                <div className="service-table-wrapper">
                    <table className="service-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名前</th>
                                <th>カテゴリ</th>
                                <th>価格</th>
                                <th>所要時間</th>
                                <th>特徴</th>
                                <th>人気</th>
                                <th>公開</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredServices.map((service) => (
                                <tr key={service.id}>
                                    <td>{service.id}</td>
                                    <td>{service.name}</td>
                                    <td>{service.category || "-"}</td>
                                    <td>¥{service.price}</td>
                                    <td>{service.duration_minutes}分</td>
                                    <td>
                                        {service.features &&
                                            service.features.length > 0 ? (
                                            <ul className="service-features-list">
                                                {service.features.map(
                                                    (f, idx) => (
                                                        <li key={idx}>
                                                            {f}
                                                        </li>
                                                    )
                                                )}
                                            </ul>
                                        ) : (
                                            <span className="service-features-empty">
                                                なし
                                            </span>
                                        )}
                                    </td>
                                    <td>
                                        {service.is_popular ? (
                                            <span className="service-popular-label">
                                                人気
                                            </span>
                                        ) : (
                                            <span className="service-popular-empty">
                                                -
                                            </span>
                                        )}
                                    </td>
                                    {/* 公開/非公開切替ボタン */}
                                    <td>
                                        <button
                                            onClick={() =>
                                                toggleActive(service.id)
                                            }
                                            className={
                                                "service-active-toggle " +
                                                (service.is_active
                                                    ? "service-active-toggle--active"
                                                    : "service-active-toggle--inactive")
                                            }
                                        >
                                            {service.is_active ? "公開" : "非公開"}
                                        </button>
                                    </td>
                                    <td className="service-actions-cell">
                                        <Link
                                            href={route(
                                                "admin.services.edit",
                                                service.id
                                            )}
                                            className="service-action-link service-action-link--edit"
                                        >
                                            編集
                                        </Link>
                                        <button
                                            onClick={() =>
                                                handleDelete(service.id)
                                            }
                                            className="service-action-link service-action-link--delete"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
