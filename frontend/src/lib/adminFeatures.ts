// /home/ri309/new-app/frontend/src/lib/adminFeatures.ts

/**
 * lucide-react が未インストール/解決できない環境でも型だけで破綻しにくいように
 * 「React component 型」として受けるようにする。
 *
 * ※ lucide-react を使うなら最終的には
 *   npm i lucide-react
 * が推奨です。
 */

import type React from "react";

// lucide の icon component はだいたいこの形（propsは任意）
export type IconComponent = React.ComponentType<
    React.SVGProps<SVGSVGElement> & { size?: number | string; color?: string }
>;

// lucide-react が入っている前提で import（未インストールならここでエラーになる）
import { CalendarDays, Clock, ShoppingCart } from "lucide-react";

/**
 * 管理ダッシュボードの「機能カード」定義
 */
export type AdminFeature = {
    title: string;
    description: string;
    href: string;
    icon: IconComponent;
    colorClass?: string;
    permission?: string;
};

export const adminFeatures: AdminFeature[] = [
    {
        title: "予約管理",
        description: "全てのお客様の予約一覧を管理します。",
        href: "/admin/reservations",
        icon: CalendarDays as unknown as IconComponent,
        colorClass: "bg-indigo-600",
        permission: "admin.reservations",
    },
    {
        title: "営業時間設定",
        description: "営業日と時間帯を設定・更新します。",
        href: "/admin/business-hours",
        icon: Clock as unknown as IconComponent,
        colorClass: "bg-amber-700",
        permission: "admin.business_hours",
    },
    {
        title: "サービス管理",
        description: "サロンで提供するサービス一覧の作成・編集・削除を行います。",
        href: "/admin/services",
        icon: ShoppingCart as unknown as IconComponent,
        colorClass: "bg-green-600",
        permission: "admin.services",
    },
];

/**
 * 互換: useAdmin.js の checkPermission 相当（将来用）
 */
export function checkPermission(_permission?: string): boolean {
    return true;
}
