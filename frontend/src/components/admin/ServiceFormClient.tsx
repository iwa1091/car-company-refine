// frontend/src/components/admin/ServiceFormClient.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import CategoryModal from "./CategoryModal";
import { apiFormData } from "@/lib/apiFormData";

type Category = { id: number; name: string };

type Service = {
    id: number;
    name: string;
    description: string | null;
    price: number; // hidden 送信用
    price_text: string | null; // hidden 送信用
    duration_minutes: number;
    sort_order: number;
    is_active: boolean;
    is_popular: boolean;
    category_id: number | null;
    features: string[];
    image_url?: string | null;
};

type Props = {
    service?: Service | null;
    categories?: Category[];
};

type ErrorMap = Record<string, string>;

const MAX_IMAGE_BYTES = 200 * 1024;

// ★あなたの Laravel API に合わせて必要なら変更
const ENDPOINTS = {
    store: "/api/admin/services",
    update: (id: number) => `/api/admin/services/${id}`,
};

export default function ServiceFormClient({ service = null, categories: initialCategories = [] }: Props) {
    const router = useRouter();

    const [categories, setCategories] = useState<Category[]>(Array.isArray(initialCategories) ? initialCategories : []);
    const [showModal, setShowModal] = useState(false);

    const [message, setMessage] = useState("");
    const [processing, setProcessing] = useState(false);

    // Inertia の useForm 相当
    const [data, setData] = useState(() => ({
        name: service?.name ?? "",
        description: service?.description ?? "",
        price: service?.price ?? 0,
        price_text: service?.price_text ?? "",
        duration_minutes: service?.duration_minutes ? String(service.duration_minutes) : "",
        sort_order: service?.sort_order ?? 0,
        is_active: !!service?.is_active,
        is_popular: !!service?.is_popular,
        category_id: service?.category_id ? String(service.category_id) : "",
        features: Array.isArray(service?.features) ? service!.features : ([] as string[]),
        image: null as File | null,
    }));

    const [featureInput, setFeatureInput] = useState("");
    const [imageError, setImageError] = useState("");
    const [clientErrors, setClientErrors] = useState<ErrorMap>({});
    const [serverErrors, setServerErrors] = useState<ErrorMap>({});

    // props 更新に追従（必要なら）
    useEffect(() => {
        setCategories(Array.isArray(initialCategories) ? initialCategories : []);
    }, [initialCategories?.length]);

    const sortedCategories = useMemo(() => {
        return Array.isArray(categories) ? categories : [];
    }, [categories]);

    const getError = (field: string) => clientErrors[field] || serverErrors[field] || "";

    const clearClientError = (field: string) => {
        setClientErrors((prev) => {
            if (!prev[field]) return prev;
            const next = { ...prev };
            delete next[field];
            return next;
        });
    };

    const handleCategoryCreated = (newCategory: Category) => {
        if (!newCategory?.id) return;

        setCategories((prev) => {
            const exists = prev.some((c) => c.id === newCategory.id);
            return exists ? prev : [...prev, newCategory];
        });

        setData((prev) => ({ ...prev, category_id: String(newCategory.id) }));
        setShowModal(false);

        // category_id の client error を消す
        setClientErrors((prev) => {
            if (!prev.category_id) return prev;
            const next = { ...prev };
            delete next.category_id;
            return next;
        });
    };

    const handleChange: React.ChangeEventHandler<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement> = (e) => {
        const target = e.target as HTMLInputElement;
        const { name, type } = target;

        clearClientError(name);
        setServerErrors({}); // 入力したら一旦サーバエラーを薄めたい場合

        if (type === "checkbox") {
            setData((prev) => ({ ...prev, [name]: target.checked }));
            return;
        }

        if (type === "file") {
            const file = target.files?.[0] ?? null;

            if (name === "image") {
                if (!file) {
                    setImageError("");
                    setData((prev) => ({ ...prev, image: null }));
                    return;
                }

                if (file.size > MAX_IMAGE_BYTES) {
                    const msg = "画像は200KB以下のファイルを選択してください。";
                    setImageError(msg);
                    setData((prev) => ({ ...prev, image: null }));
                    // 同じファイルを選び直せるようにリセット
                    target.value = "";
                    setClientErrors((prev) => ({ ...prev, image: msg }));
                    return;
                }

                setImageError("");
                setClientErrors((prev) => {
                    if (!prev.image) return prev;
                    const next = { ...prev };
                    delete next.image;
                    return next;
                });
                setData((prev) => ({ ...prev, image: file }));
                return;
            }

            // 画像以外（今は無いが既存仕様を壊さない）
            setData((prev) => ({ ...prev, [name]: file }));
            return;
        }

        setData((prev) => ({ ...prev, [name]: (target as any).value }));
    };

    const handleFeatureKeyDown: React.KeyboardEventHandler<HTMLInputElement> = (e) => {
        // IME 変換中は無視
        if ((e as any).isComposing || (e as any).keyCode === 229) return;

        if (e.key === "Enter") {
            e.preventDefault();
            const trimmed = featureInput.trim();
            if (trimmed && !data.features.includes(trimmed)) {
                setData((prev) => ({ ...prev, features: [...prev.features, trimmed] }));
                clearClientError("features");
            }
            setFeatureInput("");
        }
    };

    const removeFeature = (feature: string) => {
        setData((prev) => ({ ...prev, features: prev.features.filter((f) => f !== feature) }));
    };

    const validate = (): ErrorMap => {
        const errs: ErrorMap = {};

        const name = String(data.name || "").trim();
        const categoryId = String(data.category_id || "").trim();

        const duration = Number(data.duration_minutes);
        const sortOrder = Number(data.sort_order);

        if (!name) errs.name = "メニュー名は必須です。";
        else if (name.length > 255) errs.name = "メニュー名は255文字以内で入力してください。";

        if (!categoryId) errs.category_id = "カテゴリーを選択してください。";

        if (data.description != null) {
            const desc = String(data.description);
            if (desc.length > 2000) errs.description = "説明は2000文字以内で入力してください。";
        }

        if (!Number.isFinite(duration) || duration <= 0) errs.duration_minutes = "所要時間は必須です。1以上の数値で入力してください。";
        else if (duration < 1 || duration > 480) errs.duration_minutes = "所要時間は1〜480の範囲で入力してください。";
        else if (!Number.isInteger(duration)) errs.duration_minutes = "所要時間は整数で入力してください。";

        if (data.sort_order !== null && data.sort_order !== undefined && String(data.sort_order) !== "") {
            if (!Number.isFinite(sortOrder) || sortOrder < 0) errs.sort_order = "表示順序は0以上の数値で入力してください。";
            else if (!Number.isInteger(sortOrder)) errs.sort_order = "表示順序は整数で入力してください。";
        }

        if (imageError) errs.image = imageError;

        return errs;
    };

    const handleSubmit: React.FormEventHandler<HTMLFormElement> = async (e) => {
        e.preventDefault();

        const errs = validate();
        setClientErrors(errs);
        if (Object.keys(errs).length > 0) return;

        setProcessing(true);
        setMessage("");
        setServerErrors({});

        try {
            const formData = new FormData();

            // hidden: price / price_text も送る（既存必須対策）
            formData.append("price", String(data.price ?? 0));
            formData.append("price_text", String(data.price_text ?? ""));

            formData.append("name", data.name);
            formData.append("description", data.description ?? "");
            formData.append("duration_minutes", String(data.duration_minutes));
            formData.append("sort_order", String(data.sort_order ?? 0));
            formData.append("is_active", data.is_active ? "1" : "0");
            formData.append("is_popular", data.is_popular ? "1" : "0");
            formData.append("category_id", String(data.category_id));

            data.features.forEach((f) => formData.append("features[]", f));

            if (data.image) {
                formData.append("image", data.image);
            }

            // Laravel 側が PUT を受けない/Route Handler 経由で POST が楽なら _method を使う
            if (service?.id) {
                formData.append("_method", "PUT");
            }

            const res = await apiFormData(
                service?.id ? ENDPOINTS.update(service.id) : ENDPOINTS.store,
                {
                    method: "POST",
                    body: formData,
                }
            );


            if (res.ok) {
                setMessage("保存しました。");
                // 一覧へ戻す（必要なら遷移先は調整）
                router.push("/admin/services");
                return;
            }

            // Laravel validation想定: { message, errors: { field: [...] } }
            const json = (await res.json().catch(() => null)) as any;
            const nextErrors: ErrorMap = {};

            if (json?.errors && typeof json.errors === "object") {
                for (const [k, v] of Object.entries(json.errors)) {
                    const arr = Array.isArray(v) ? v : [String(v)];
                    nextErrors[k] = String(arr[0] ?? "");
                }
            }

            setServerErrors(nextErrors);
            setMessage(json?.message || "保存に失敗しました。");
        } catch (err) {
            console.error(err);
            setMessage("サーバー通信エラーが発生しました。");
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="admin-service-form-page">
            <div className="admin-service-form-container">
                {/* 🔙 サービス一覧へ戻る */}
                <div className="service-form-back-area">
                    <Link href="/admin/services" className="service-form-back-button">
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="service-form-title">{service ? "サービス編集" : "新規サービス作成"}</h1>

                {message && (
                    <div style={{ marginBottom: "0.75rem", fontWeight: 800, color: "rgba(234,241,255,0.88)" }}>
                        {message}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="service-form" encType="multipart/form-data" noValidate>
                    {/* hidden: price / price_text */}
                    <input type="hidden" name="price" value={data.price} readOnly />
                    <input type="hidden" name="price_text" value={data.price_text} readOnly />

                    {/* 名前 */}
                    <div className="service-form-field">
                        <label className="service-form-label">メニュー名</label>
                        <input type="text" name="name" value={data.name} onChange={handleChange} className="service-form-input" required />
                        {!!getError("name") && <div className="service-form-error">{getError("name")}</div>}
                    </div>

                    {/* カテゴリ */}
                    <div className="service-form-field">
                        <label className="service-form-label">カテゴリー名</label>
                        <div className="service-form-category-row">
                            <select name="category_id" value={data.category_id} onChange={handleChange} className="service-form-select" required>
                                <option value="">選択してください</option>
                                {sortedCategories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>

                            <button type="button" className="service-form-category-add" onClick={() => setShowModal(true)}>
                                ＋新規作成
                            </button>
                        </div>
                        {!!getError("category_id") && <div className="service-form-error">{getError("category_id")}</div>}
                    </div>

                    {/* 説明 */}
                    <div className="service-form-field">
                        <label className="service-form-label">説明</label>
                        <textarea name="description" value={data.description} onChange={handleChange} className="service-form-textarea" rows={4} />
                        {!!getError("description") && <div className="service-form-error">{getError("description")}</div>}
                    </div>

                    {/* 所要時間 */}
                    <div className="service-form-field">
                        <label className="service-form-label">所要時間 (分)</label>
                        <input
                            type="number"
                            name="duration_minutes"
                            value={data.duration_minutes}
                            onChange={handleChange}
                            className="service-form-input"
                            min={1}
                            max={480}
                            required
                        />
                        {!!getError("duration_minutes") && <div className="service-form-error">{getError("duration_minutes")}</div>}
                    </div>

                    {/* 表示順序 */}
                    <div className="service-form-field">
                        <label className="service-form-label">表示順序</label>
                        <input type="number" name="sort_order" value={data.sort_order} onChange={handleChange} className="service-form-input" min={0} />
                        {!!getError("sort_order") && <div className="service-form-error">{getError("sort_order")}</div>}
                    </div>

                    {/* 公開 */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input type="checkbox" name="is_active" checked={data.is_active} onChange={handleChange} className="service-form-checkbox" />
                            公開
                        </label>
                    </div>

                    {/* 人気 */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input type="checkbox" name="is_popular" checked={data.is_popular} onChange={handleChange} className="service-form-checkbox" />
                            人気サービス
                        </label>
                    </div>

                    {/* 特徴 */}
                    <div className="service-form-field">
                        <label className="service-form-label">特徴</label>
                        <input
                            type="text"
                            value={featureInput}
                            onChange={(e) => setFeatureInput(e.target.value)}
                            onKeyDown={handleFeatureKeyDown}
                            placeholder="Enterで追加"
                            className="service-form-input service-form-feature-input"
                            autoComplete="off"
                        />

                        <div className="service-features-container">
                            {data.features.map((f, idx) => (
                                <span key={`${f}-${idx}`} className="service-feature-chip">
                                    {f}
                                    <button type="button" className="service-feature-chip-remove" onClick={() => removeFeature(f)}>
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>

                        {!!getError("features") && <div className="service-form-error">{getError("features")}</div>}
                    </div>

                    {/* 画像 */}
                    <div className="service-form-field">
                        <label className="service-form-label">画像アップロード</label>
                        <input type="file" name="image" onChange={handleChange} className="service-form-input" accept="image/*" />

                        <div style={{ marginTop: "6px", fontSize: "0.82rem", fontWeight: 700, color: "rgba(234, 241, 255, 0.65)", lineHeight: 1.4 }}>
                            画像容量は200KB以下にしてください。
                        </div>

                        {imageError && <div className="service-form-error">{imageError}</div>}

                        {service?.image_url && (
                            <img src={service.image_url} alt="Current" className="service-form-image-preview" />
                        )}

                        {!!getError("image") && <div className="service-form-error">{getError("image")}</div>}
                    </div>

                    {/* 保存 */}
                    <button type="submit" disabled={processing || !!imageError} className="service-form-submit-button">
                        保存
                    </button>
                </form>

                <CategoryModal
                    isOpen={showModal}
                    onClose={() => setShowModal(false)}
                    onCreated={handleCategoryCreated}
                />
            </div>
        </div>
    );
}
