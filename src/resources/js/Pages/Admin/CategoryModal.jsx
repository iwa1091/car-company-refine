// /resources/js/Pages/Admin/CategoryModal.jsx
import React, { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { router, usePage } from "@inertiajs/react";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/category-modal.css";

export default function CategoryModal({ isOpen, onClose, onCreated }) {
    const { props } = usePage();
    const flashCategory = props?.flash?.category;

    const [name, setName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    // ✅ もしサーバーが flash.category を返してくる構成なら、
    // 成功後に自動で拾って親へ渡す（成功時にモーダルが閉じない問題を潰す）
    useEffect(() => {
        if (!isOpen) return;
        if (!flashCategory) return;

        // flash に category が来たら親へ通知して閉じる
        if (onCreated) onCreated(flashCategory);
        setName("");
        onClose?.();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flashCategory, isOpen]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        router.post(
            "/admin/categories",
            { name },
            {
                preserveScroll: true,
                // モーダル内のPOSTでページ状態を壊したくない場合に有効
                // preserveState: true, // 必要ならON（プロジェクト方針に合わせて）
                onSuccess: (arg) => {
                    // Inertiaのバージョン差を吸収（page が来る場合 / event.detail.page の場合）
                    const page = arg?.props ? arg : arg?.detail?.page;

                    const createdCategory =
                        page?.props?.flash?.category ||
                        page?.props?.category ||
                        null;

                    // ✅ flash が共有されていない構成でも、page.props 側から拾えるなら即反映
                    if (createdCategory && onCreated) {
                        onCreated(createdCategory);
                        setName("");
                        onClose?.();
                    }
                },
                onError: (err) => {
                    // Laravel バリデーションエラーを errors にセット
                    setErrors(err);
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <>
                    {/* 背景オーバーレイ */}
                    <motion.div
                        className="category-modal-overlay"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                    />

                    {/* モーダル本体 */}
                    <motion.div
                        className="category-modal-wrapper"
                        initial={{ opacity: 0, scale: 0.9, y: -30 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: -30 }}
                        transition={{ duration: 0.25 }}
                    >
                        <div className="category-modal-content">
                            <h2 className="category-modal-title">
                                新規カテゴリー作成
                            </h2>

                            <form
                                onSubmit={handleSubmit}
                                className="category-modal-form"
                            >
                                <div className="category-modal-field">
                                    <label
                                        htmlFor="category-name"
                                        className="category-modal-label"
                                    >
                                        カテゴリー名
                                    </label>
                                    <input
                                        id="category-name"
                                        type="text"
                                        value={name}
                                        onChange={(e) =>
                                            setName(e.target.value)
                                        }
                                        className="category-modal-input"
                                        placeholder="例: CERAMIC COATING"
                                        disabled={loading}
                                    />
                                    {errors.name && (
                                        <p className="category-modal-error">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="category-modal-actions">
                                    <button
                                        type="button"
                                        className="category-modal-button category-modal-button--cancel"
                                        onClick={onClose}
                                        disabled={loading}
                                    >
                                        キャンセル
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading || !name.trim()}
                                        className="category-modal-button category-modal-button--submit"
                                    >
                                        {loading ? "保存中..." : "保存"}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}
