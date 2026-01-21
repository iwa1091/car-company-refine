// /frontend/src/app/admin/services/create/page.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import ServiceFormClient from "@/components/admin/ServiceFormClient";
import { apiJson } from "@/lib/apiFetch";

type Category = { id: number; name: string };

type Service = {
    id: number;
    name: string;
    description?: string | null;
    duration_minutes: number;
    price?: number | null;
    price_text?: string | null;
    sort_order?: number | null;
    is_active: boolean | number;
    is_popular?: boolean | number;
    category_id: number | null;
    image_url?: string | null;
    features?: string[] | null;
};

type ServiceFormProps = {
    categories: Category[];
    service?: Service | null;
};

export default function AdminServiceCreatePage() {
    const searchParams = useSearchParams();

    // /admin/services/create?edit=2
    const editId = useMemo(() => {
        const v = searchParams.get("edit");
        if (!v) return null;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    }, [searchParams]);

    const isEdit = !!editId;

    const [categories, setCategories] = useState<Category[]>([]);
    const [service, setService] = useState<Service | null>(null);

    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState("");

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoading(true);
            setMessage("");

            try {
                // ① categories（一覧と同じ前提）
                const data = await apiJson<{ categories: Category[] }>("/api/admin/services", { method: "GET" });
                if (cancelled) return;
                setCategories(Array.isArray(data?.categories) ? data.categories : []);

                // ② 編集なら service 詳細
                if (editId) {
                    const detail = await apiJson<{ service?: Service } | Service>(`/api/admin/services/${editId}`, {
                        method: "GET",
                    });
                    if (cancelled) return;

                    const s: Service = (detail as any)?.service ? (detail as any).service : (detail as Service);
                    setService(s);
                } else {
                    setService(null);
                }
            } catch (e: any) {
                if (cancelled) return;

                const msg =
                    e?.data?.message ||
                    e?.message ||
                    (e?.data?.errors ? Object.values(e.data.errors).flat().join("\n") : "") ||
                    (isEdit ? "編集データの取得に失敗しました。" : "カテゴリーの取得に失敗しました。");

                setMessage(msg);
                setCategories([]);
                setService(null);
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [editId, isEdit]);

    if (loading) return <div style={{ padding: "1rem" }}>読み込み中...</div>;

    if (message) {
        return (
            <div style={{ padding: "1rem" }}>
                <div style={{ marginBottom: "0.75rem" }}>
                    <Link href="/admin/services">← サービス一覧へ戻る</Link>
                </div>
                <div style={{ whiteSpace: "pre-wrap" }}>{message}</div>
            </div>
        );
    }

    const ServiceForm = ServiceFormClient as unknown as React.ComponentType<ServiceFormProps>;

    return (
        <ServiceForm
            key={editId ?? "new"}          // ✅ editId 切替時のフォーム初期化
            categories={categories}
            service={service}
        />
    );
}
