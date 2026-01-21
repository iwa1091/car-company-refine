// /home/ri309/new-app/frontend/src/app/admin/layout.tsx

import type { ReactNode } from "react";

// ✅ admin配下で共通に使うCSSをここで読み込む
import "@/styles/pages/admin/login/login.css";
import "@/styles/pages/admin/dashboard.css";

// src/app/admin/layout.tsx
import "react-calendar/dist/Calendar.css";
import "@/styles/pages/admin/reservation-list.css";
import "@/styles/pages/admin/timetable.css";
import "@/styles/pages/admin/reservation-edit.css";
import "@/styles/pages/admin/business-hours.css";
import "@/styles/pages/admin/service-index.css";
import "@/styles/pages/admin/service-form.css";
import "@/styles/pages/admin/category-modal.css";




/**
 * Admin Shell
 * - Dashboard.jsx の AdminLayout 構造に寄せた共通レイアウト
 * - 各ページ(page.tsx)は `header` を差し込むために
 *   `AdminShell` を使って header を渡す（推奨）
 *
 * 使い方（例）:
 *   <AdminShell header={<h2 className="dashboard-title">管理者ダッシュボード</h2>}>
 *     ...contents...
 *   </AdminShell>
 *
 * ※ layout.tsx 自体は全ページ共通の枠だけを提供し、
 *   header はページ側で自由に作れるようにする。
 */

// ✅ ページ側で使う “共通シェル”
export function AdminShell({
    header,
    children,
}: {
    header?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="admin-layout">
            <header className="admin-header-bar">
                <div className="oh-container admin-header-inner">
                    {header ?? null}
                </div>
            </header>

            <main className="admin-main">{children}</main>
        </div>
    );
}

/**
 * /admin 配下の layout
 * - ここは “枠だけ提供”
 * - header をここで固定してしまうと page 側の自由度が落ちるため、
 *   header は AdminShell 経由で page が差し込む設計にする
 */
export default function AdminLayout({ children }: { children: ReactNode }) {
    return children;
}
