// /frontend/src/app/admin/services/page.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { apiFetch, apiJson } from "@/lib/apiFetch";

type Category = {
    id: number;
    name: string;
};

type Service = {
    id: number;
    name: string;
    category_id: number | null;
    category?: string | null; // 既存JSXの service.category 表示に合わせて残す
    duration_minutes: number;
    features?: string[] | null;
    is_active: boolean | number;
};

type IndexData = {
    services: Service[];
    categories: Category[];
};

export default function AdminServiceIndexPage() {
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState<string>("");
    const [services, setServices] = useState<Service[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [filterCategory, setFilterCategory] = useState<number | "">("");

    // 初期データ取得（services + categories）
    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoading(true);
            setMessage("");
            try {
                // ✅ 例：Laravel側でこのJSONを返すようにする
                // GET /api/admin/services  -> { services: [...], categories: [...] }
                const data = await apiJson<IndexData>("/api/admin/services", { method: "GET" });

                if (cancelled) return;
                setServices(Array.isArray(data?.services) ? data.services : []);
                setCategories(Array.isArray(data?.categories) ? data.categories : []);
            } catch (e: any) {
                if (cancelled) return;
                setServices([]);
                setCategories([]);
                setMessage(e?.message || "サービス一覧の取得に失敗しました。");
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    const filteredServices = useMemo(() => {
        if (filterCategory === "") return services;
        return services.filter((s) => Number(s.category_id) === Number(filterCategory));
    }, [services, filterCategory]);

    const handleDelete = async (id: number) => {
        if (!confirm("本当に削除しますか？")) return;

        try {
            // ✅ 例：DELETE /api/admin/services/{id}
            const res = await apiFetch(`/api/admin/services/${id}`, { method: "DELETE" });

            if (!res.ok) {
                const data = await res.json().catch(() => null) as any;
                throw new Error(
                    data?.message ||
                    (data?.errors ? Object.values(data.errors).flat().join("\n") : "") ||
                    "削除に失敗しました。"
                );
            }

            setServices((prev) => prev.filter((s) => s.id !== id));
        } catch (e: any) {
            setMessage(e?.message || "削除に失敗しました。");
        }
    };

    const toggleActive = async (serviceId: number) => {
        try {
            // ✅ 例：PATCH /api/admin/services/{id}/toggle -> { service: {...} } または Service 単体を返す
            const data = await apiJson<{ service?: Service } | Service>(
                `/api/admin/services/${serviceId}/toggle`,
                { method: "PATCH" }
            );

            const updated =
                (data as any)?.service ? (data as any).service as Service : (data as Service);

            setServices((prev) => prev.map((s) => (s.id === serviceId ? updated : s)));
        } catch (e: any) {
            setMessage(e?.message || "公開/非公開の切り替えに失敗しました。");
        }
    };

    if (loading) {
        return (
            <div className="admin-service-page">
                <div className="admin-service-container">
                    <p className="business-hours-loading">読み込み中...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="admin-service-page">
            <div className="admin-service-container">
                {/* 🔙 戻る */}
                <div className="service-back-area">
                    <Link href="/admin" className="service-back-button">
                        前のページに戻る
                    </Link>
                </div>

                {/* ヘッダー（タイトル + 新規作成） */}
                <div className="service-page-header">
                    <h1 className="service-page-title">サービス一覧</h1>
                    <Link href="/admin/services/create" className="service-create-button">
                        新規作成
                    </Link>
                </div>

                {message ? <p className="business-hours-message">{message}</p> : null}

                {/* フィルタ */}
                <div className="service-filter">
                    <label className="service-filter-label">コースで絞り込み:</label>
                    <select
                        value={filterCategory === "" ? "" : String(filterCategory)}
                        onChange={(e) => {
                            const v = e.target.value;
                            setFilterCategory(v === "" ? "" : Number(v));
                        }}
                        className="service-filter-select"
                    >
                        <option value="">すべて</option>
                        {categories.map((cat) => (
                            <option key={cat.id} value={String(cat.id)}>
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
                                <th>コース</th>
                                <th>所要時間</th>
                                <th>特徴</th>
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
                                    <td>{service.duration_minutes}分</td>

                                    <td>
                                        {service.features && service.features.length > 0 ? (
                                            <ul className="service-features-list">
                                                {service.features.map((f, idx) => (
                                                    <li key={idx}>{f}</li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <span className="service-features-empty">なし</span>
                                        )}
                                    </td>
                                    
                                    <td>
                                        <button
                                            type="button"
                                            onClick={() => toggleActive(service.id)}
                                            className={
                                                "service-active-toggle " +
                                                (Boolean(service.is_active)
                                                    ? "service-active-toggle--active"
                                                    : "service-active-toggle--inactive")
                                            }
                                        >
                                            {Boolean(service.is_active) ? "公開" : "非公開"}
                                        </button>
                                    </td>

                                    <td className="service-actions-cell">
                                        <Link
                                            href={`/admin/services/create?edit=${service.id}`}
                                            className="service-action-link service-action-link--edit"
                                        >
                                            編集
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => handleDelete(service.id)}
                                            className="service-action-link service-action-link--delete"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {filteredServices.length === 0 ? (
                                <tr>
                                    <td colSpan={8} style={{ textAlign: "center", padding: "1rem" }}>
                                        表示できるサービスがありません
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
