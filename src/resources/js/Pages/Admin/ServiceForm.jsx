// /resources/js/Pages/Admin/ServiceForm.jsx
import React, { useEffect, useMemo, useState } from "react";
import { useForm, usePage, Link, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import CategoryModal from "./CategoryModal";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/service-form.css";

export default function ServiceForm({
    service = null,
    categories: initialCategories = [],
}) {
    const { errors, flash } = usePage().props || {};
    const flashCategory = flash?.category ?? null;

    // ✅ 200KB（クライアント側チェック）
    const MAX_IMAGE_BYTES = 200 * 1024;

    // ✅ Inertia の useForm フックを使用
    const { data, setData, processing } = useForm({
        name: service?.name || "",
        description: service?.description || "",
        price: service?.price || "",

        // ✅ 追加：価格テキスト（任意）
        price_text: service?.price_text || "",

        duration_minutes: service?.duration_minutes || "",
        sort_order: service?.sort_order || 0,
        is_active: !!service?.is_active,
        is_popular: !!service?.is_popular,
        category_id: service?.category_id || "",
        features: Array.isArray(service?.features) ? service.features : [],
        image: null,
    });

    // ★ props更新に追従できるように state を同期
    const [categories, setCategories] = useState(initialCategories);
    const [showModal, setShowModal] = useState(false);
    const [featureInput, setFeatureInput] = useState("");

    // ✅ 画像サイズエラー（クライアント側）
    const [imageError, setImageError] = useState("");

    // ✅ props 側の categories が更新されたら state も更新（InertiaのPOST/リダイレクト後の差分反映）
    useEffect(() => {
        setCategories(Array.isArray(initialCategories) ? initialCategories : []);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialCategories?.length]);

    /** ✅ カテゴリ新規作成後に即反映（重複防止） */
    const handleCategoryCreated = (newCategory) => {
        if (!newCategory?.id) return;

        setCategories((prev) => {
            const exists = prev?.some((c) => c?.id === newCategory.id);
            return exists ? prev : [...(prev || []), newCategory];
        });

        setData("category_id", newCategory.id);
        setShowModal(false);
    };

    // ✅ もし flash.category が共有される構成なら、親側でも拾って確実に反映＆モーダルを閉じる
    useEffect(() => {
        if (!flashCategory?.id) return;
        handleCategoryCreated(flashCategory);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flashCategory?.id]);

    /** ✅ 入力変更 */
    const handleChange = (e) => {
        const { name, type, checked, files, value } = e.target;

        if (type === "checkbox") {
            setData(name, checked);
        } else if (type === "file") {
            const file = files?.[0] ?? null;

            // ✅ 画像だけ 200KB 制限（他の file input が増えても壊さない）
            if (name === "image") {
                // 未選択（取り消し）
                if (!file) {
                    setImageError("");
                    setData(name, null);
                    return;
                }

                if (file.size > MAX_IMAGE_BYTES) {
                    setImageError("画像は200KB以下のファイルを選択してください。");
                    setData(name, null);

                    // 同じファイルを選び直せるようにリセット
                    e.target.value = "";
                    return;
                }

                // OK
                setImageError("");
                setData(name, file);
                return;
            }

            // 画像以外（現状は無いが既存仕様を壊さない）
            setData(name, file);
        } else {
            setData(name, value);
        }
    };

    /** ✅ 特徴追加（Enterキー） */
    const handleFeatureKeyDown = (e) => {
        if (e.isComposing || e.keyCode === 229) return; // IME変換中はスキップ

        if (e.key === "Enter") {
            e.preventDefault();
            const trimmed = featureInput.trim();

            if (trimmed && !data.features.includes(trimmed)) {
                setData("features", [...data.features, trimmed]);
            }
            setFeatureInput("");
        }
    };

    /** ✅ 特徴削除 */
    const removeFeature = (feature) => {
        setData(
            "features",
            data.features.filter((f) => f !== feature)
        );
    };

    const sortedCategories = useMemo(() => {
        const list = Array.isArray(categories) ? categories : [];
        // 既存仕様を壊さないため「そのまま」でもOKだが、見やすいように id 昇順に揃えるならここで
        return list;
    }, [categories]);

    /** ✅ 保存処理 */
    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();

        Object.entries(data).forEach(([key, value]) => {
            if (key === "features" && Array.isArray(value)) {
                value.forEach((feature) => {
                    formData.append("features[]", feature);
                });
            } else if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        if (service) {
            // 更新時は Laravel 側で PUT として扱わせる
            formData.append("_method", "PUT");

            router.post(route("admin.services.update", service.id), formData, {
                forceFormData: true,
                preserveScroll: true,
            });
        } else {
            router.post(route("admin.services.store"), formData, {
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="admin-service-form-page">
            <div className="admin-service-form-container">
                {/* 🔙 サービス一覧へ戻る */}
                <div className="service-form-back-area">
                    <Link
                        href={route("admin.services.index")}
                        className="service-form-back-button"
                    >
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="service-form-title">
                    {service ? "サービス編集" : "新規サービス作成"}
                </h1>

                <form
                    onSubmit={handleSubmit}
                    className="service-form"
                    encType="multipart/form-data"
                >
                    {/* 名前 */}
                    <div className="service-form-field">
                        <label className="service-form-label">名前</label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={handleChange}
                            className="service-form-input"
                            required
                        />
                        {errors?.name && (
                            <div className="service-form-error">
                                {errors.name}
                            </div>
                        )}
                    </div>

                    {/* カテゴリ */}
                    <div className="service-form-field">
                        <label className="service-form-label">カテゴリー名</label>
                        <div className="service-form-category-row">
                            <select
                                name="category_id"
                                value={data.category_id}
                                onChange={handleChange}
                                className="service-form-select"
                                required
                            >
                                <option value="">選択してください</option>
                                {sortedCategories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>

                            {/* ✅ 新規カテゴリ追加ボタン */}
                            <button
                                type="button"
                                className="service-form-category-add"
                                onClick={() => setShowModal(true)}
                            >
                                ＋新規作成
                            </button>
                        </div>
                        {errors?.category_id && (
                            <div className="service-form-error">
                                {errors.category_id}
                            </div>
                        )}
                    </div>

                    {/* 説明 */}
                    <div className="service-form-field">
                        <label className="service-form-label">説明</label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={handleChange}
                            className="service-form-textarea"
                            rows="4"
                        />
                        {errors?.description && (
                            <div className="service-form-error">
                                {errors.description}
                            </div>
                        )}
                    </div>

                    {/* 価格 */}
                    <div className="service-form-field">
                        <label className="service-form-label">価格 (円)</label>
                        <input
                            type="text"
                            name="price"
                            value={data.price}
                            onChange={handleChange}
                            className="service-form-input"
                            inputMode="text"
                        />
                        {errors?.price && (
                            <div className="service-form-error">
                                {errors.price}
                            </div>
                        )}
                    </div>

                    {/* ✅ 追加：価格テキスト（任意） */}
                    <div className="service-form-field">
                        <label className="service-form-label">価格テキスト（任意）</label>
                        <input
                            type="text"
                            name="price_text"
                            value={data.price_text}
                            onChange={handleChange}
                            className="service-form-input"
                            placeholder="例：10,000円 / 要相談 / キャンペーン中 など"
                            maxLength={255}
                        />
                        {errors?.price_text && (
                            <div className="service-form-error">
                                {errors.price_text}
                            </div>
                        )}
                    </div>

                    {/* 所要時間 */}
                    <div className="service-form-field">
                        <label className="service-form-label">
                            所要時間 (分)
                        </label>
                        <input
                            type="number"
                            name="duration_minutes"
                            value={data.duration_minutes}
                            onChange={handleChange}
                            className="service-form-input"
                            min="1"
                            max="480"
                            required
                        />
                        {errors?.duration_minutes && (
                            <div className="service-form-error">
                                {errors.duration_minutes}
                            </div>
                        )}
                    </div>

                    {/* 表示順序 */}
                    <div className="service-form-field">
                        <label className="service-form-label">表示順序</label>
                        <input
                            type="number"
                            name="sort_order"
                            value={data.sort_order}
                            onChange={handleChange}
                            className="service-form-input"
                            min="0"
                        />
                        {errors?.sort_order && (
                            <div className="service-form-error">
                                {errors.sort_order}
                            </div>
                        )}
                    </div>

                    {/* 公開 */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input
                                type="checkbox"
                                name="is_active"
                                checked={data.is_active}
                                onChange={handleChange}
                                className="service-form-checkbox"
                            />
                            公開
                        </label>
                    </div>

                    {/* 人気サービス */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input
                                type="checkbox"
                                name="is_popular"
                                checked={data.is_popular}
                                onChange={handleChange}
                                className="service-form-checkbox"
                            />
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
                                <span
                                    key={`${f}-${idx}`}
                                    className="service-feature-chip"
                                >
                                    {f}
                                    <button
                                        type="button"
                                        className="service-feature-chip-remove"
                                        onClick={() => removeFeature(f)}
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                        {errors?.features && (
                            <div className="service-form-error">
                                {errors.features}
                            </div>
                        )}
                    </div>

                    {/* 画像 */}
                    <div className="service-form-field">
                        <label className="service-form-label">
                            画像アップロード
                        </label>
                        <input
                            type="file"
                            name="image"
                            onChange={handleChange}
                            className="service-form-input"
                            accept="image/*"
                        />

                        {/* ✅ 追加：注意文言 */}
                        <div
                            style={{
                                marginTop: "6px",
                                fontSize: "0.82rem",
                                fontWeight: 700,
                                color: "rgba(234, 241, 255, 0.65)",
                                lineHeight: 1.4,
                            }}
                        >
                            画像容量は200KB以下にしてください。
                        </div>

                        {/* ✅ クライアント側（200KB超） */}
                        {imageError && (
                            <div className="service-form-error">
                                {imageError}
                            </div>
                        )}

                        {service?.image_url && (
                            <img
                                src={service.image_url}
                                alt="Current"
                                className="service-form-image-preview"
                            />
                        )}

                        {/* ✅ サーバ側（Laravel） */}
                        {errors?.image && (
                            <div className="service-form-error">
                                {errors.image}
                            </div>
                        )}
                    </div>

                    {/* 保存ボタン */}
                    <button
                        type="submit"
                        disabled={processing || !!imageError}
                        className="service-form-submit-button"
                    >
                        保存
                    </button>
                </form>

                {/* モーダル */}
                <CategoryModal
                    isOpen={showModal}
                    onClose={() => setShowModal(false)}
                    onCreated={handleCategoryCreated}
                />
            </div>
        </div>
    );
}
