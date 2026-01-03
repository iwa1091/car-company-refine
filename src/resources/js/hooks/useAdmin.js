import { ShoppingCart, CalendarDays, Users, Clock } from 'lucide-react';

export function useAdmin() {
    const adminFeatures = [
        {
            title: "予約管理",
            description: "全てのお客様の予約一覧を管理します。",
            route: 'admin.reservations.index',
            icon: CalendarDays,
            color: 'bg-indigo-600',
        },
        {
            title: "営業時間設定",
            description: "営業日と時間帯を設定・更新します。",
            route: "admin.business-hours.index",
            color: "bg-brown",
            icon: Clock,
        },

        {
            title: "サービス管理",
            description: "サロンで提供するサービス一覧の作成・編集・削除を行います。",
            route: 'admin.services.index',
            icon: ShoppingCart, // 他のアイコンでも可
            color: 'bg-green-600',
        },

    ];

    const checkPermission = (permission) => {
        console.log(`Permission check requested for: ${permission}`);
        return true;
    };

    return { adminFeatures, checkPermission };
}
