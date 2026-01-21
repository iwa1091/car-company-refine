// /home/ri309/new-app/frontend/src/app/admin/page.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import type { ReactNode } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

import { apiJson } from "@/lib/apiFetch";
import { adminFeatures, checkPermission } from "@/lib/adminFeatures";

// ✅ /admin 配下の共通シェル（Dashboard.jsx の AdminLayout 相当）
import { AdminShell } from "./layout";

type MeResponse = {
    id: number;
    name: string;
    email: string;
};

function AdminLayout({
    header,
    children,
}: {
    header: ReactNode;
    children: ReactNode;
}) {
    // ✅ 既存の AdminLayout は残しつつ、実体は AdminShell に寄せる
    return <AdminShell header={header}>{children}</AdminShell>;
}

export default function AdminDashboardPage() {
    const router = useRouter();

    const [me, setMe] = useState<MeResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    // ✅ 権限チェック（現状は常に true だけど将来に備えてここでフィルタ）
    const visibleFeatures = useMemo(() => {
        return adminFeatures.filter((f) => checkPermission(f.permission));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const loadMe = async () => {
        setLoading(true);
        setError("");
        try {
            const data = await apiJson<MeResponse>("/api/admin/me", { method: "GET" });
            setMe(data);
        } catch (err: any) {
            const status = err?.status;
            if (status === 401) {
                router.replace("/admin/login");
                return;
            }
            setError(err?.message || "取得に失敗しました。");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadMe();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const logout = async () => {
        setError("");
        try {
            await apiJson<{ ok: true }>("/api/admin/session", { method: "DELETE" });
            router.replace("/admin/login");
        } catch (err: any) {
            setError(err?.message || "ログアウトに失敗しました。");
        }
    };

    const userName = me?.name || "ゲスト管理者";

    return (
        <AdminLayout
            header={
                <div className="dashboard-header">
                    <h2 className="dashboard-title">管理者ダッシュボード</h2>

                    <div className="dashboard-actions">
                        <button className="button" onClick={logout} type="button">
                            ログアウト
                        </button>
                    </div>
                </div>
            }
        >
            <div className="dashboard-wrapper">
                <div className="oh-container">
                    <div className="dashboard-card-area">
                        {loading ? <p className="welcome-message">読み込み中...</p> : null}

                        {!loading && error ? (
                            <div className="admin-login__alert admin-login__alert--error" role="alert">
                                <p className="admin-login__alert-title">エラー</p>
                                <ul className="admin-login__alert-list">
                                    <li>{error}</li>
                                </ul>
                            </div>
                        ) : null}

                        {!loading && !error ? (
                            <>
                                <p className="welcome-message">
                                    ようこそ、<span className="user-name">{userName}</span>様。
                                    <span className="message-subtext">
                                        実行したい管理タスクを選択してください。
                                    </span>
                                </p>

                                <div className="feature-grid">
                                    {visibleFeatures.map((feature) => {
                                        const Icon = feature.icon;
                                        return (
                                            <Link key={feature.title} href={feature.href} className="feature-card">
                                                <div className={`feature-icon-wrapper ${feature.colorClass || ""}`}>
                                                    <Icon size={28} strokeWidth={2.5} />
                                                </div>
                                                <h3 className="card-title">{feature.title}</h3>
                                                <p className="card-description">{feature.description}</p>
                                                <span className="card-link">詳細管理へ &rarr;</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </>
                        ) : null}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
