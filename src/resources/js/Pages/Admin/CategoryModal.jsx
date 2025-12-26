// /resources/js/Pages/Admin/CategoryModal.jsx
import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Inertia } from "@inertiajs/inertia";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/category-modal.css";

export default function CategoryModal({ isOpen, onClose, onCreated }) {
    const [name, setName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        Inertia.post(
            "/admin/categories",
            { name },
            {
                onSuccess: (page) => {
                    const createdCategory =
                        page.props.flash?.category || page.props.category;
                    if (createdCategory && onCreated) {
                        onCreated(createdCategory ?? null);
                    }
                    setName("");
                    onClose();
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
                                        placeholder="例: まつ毛エクステ"
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
                                    >
                                        キャンセル
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
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
