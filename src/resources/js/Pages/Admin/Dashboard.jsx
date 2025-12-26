// /resources/js/Pages/Admin/Dashboard.jsx

import React from "react";
import { Link, Head } from "@inertiajs/react";

import { useAdmin } from "@/hooks/useAdmin";
// CSS は resources/css/pages/admin/dashboard.css に配置する前提
import "../../../css/pages/admin/dashboard.css";

const AdminLayout = ({ header, children }) => (
    <div className="admin-layout">
        <header className="admin-header-bar">
            <div className="oh-container admin-header-inner">
                {header}
            </div>
        </header>
        <main className="admin-main">{children}</main>
    </div>
);

export default function AdminDashboard({ auth }) {
    const { adminFeatures } = useAdmin();
    const userName = auth.user?.name || "ゲスト管理者";

    return (
        <>
            {/* Inertia 標準の Head を使用 */}
            <Head title="管理者ダッシュボード" />

            <AdminLayout
                header={
                    <h2 className="dashboard-title">
                        管理者ダッシュボード
                    </h2>
                }
            >
                <div className="dashboard-wrapper">
                    <div className="oh-container">
                        <div className="dashboard-card-area">
                            <p className="welcome-message">
                                ようこそ、
                                <span className="user-name">{userName}</span>
                                様。
                                <span className="message-subtext">
                                    実行したい管理タスクを選択してください。
                                </span>
                            </p>

                            <div className="feature-grid">
                                {adminFeatures.map((feature) => (
                                    <Link
                                        key={feature.title}
                                        href={route(feature.route)}
                                        className="feature-card"
                                    >
                                        <div
                                            className={`feature-icon-wrapper ${feature.color || ""
                                                }`}
                                        >
                                            <feature.icon
                                                size={28}
                                                strokeWidth={2.5}
                                            />
                                        </div>
                                        <h3 className="card-title">
                                            {feature.title}
                                        </h3>
                                        <p className="card-description">
                                            {feature.description}
                                        </p>
                                        <span className="card-link">
                                            詳細管理へ &rarr;
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}
