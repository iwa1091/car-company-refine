"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import type { FormEvent } from "react";
import { createPortal } from "react-dom";
import { AnimatePresence, motion } from "framer-motion";
import { apiJson } from "@/lib/apiFetch";

export type Category = {
    id: number;
    name: string;
};

type Props = {
    isOpen: boolean;
    onClose: () => void;
    onCreated?: (category: Category) => void;

    /** API化しているなら "/api/admin/categories" */
    createEndpoint?: string;
};

type LaravelValidationError = {
    message?: string;
    errors?: Record<string, string[]>;
};

export default function CategoryModal({
    isOpen,
    onClose,
    onCreated,
    createEndpoint = "/api/admin/categories",
}: Props) {
    const [mounted, setMounted] = useState(false);
    const [name, setName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errorName, setErrorName] = useState<string>("");
    const [errorGeneral, setErrorGeneral] = useState<string>("");

    const inputRef = useRef<HTMLInputElement | null>(null);

    // portal 先（SSR対策）
    useEffect(() => setMounted(true), []);

    // open時：初期化 & フォーカス
    useEffect(() => {
        if (!isOpen) return;
        setErrorName("");
        setErrorGeneral("");
        setLoading(false);
        setName("");
        setTimeout(() => inputRef.current?.focus(), 0);
    }, [isOpen]);

    // ESCで閉じる
    useEffect(() => {
        if (!isOpen) return;

        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === "Escape") onClose();
        };
        window.addEventListener("keydown", onKeyDown);
        return () => window.removeEventListener("keydown", onKeyDown);
    }, [isOpen, onClose]);

    const canSubmit = useMemo(() => name.trim().length > 0 && !loading, [name, loading]);

    const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!canSubmit) return;

        setLoading(true);
        setErrorName("");
        setErrorGeneral("");

        try {
            const res = await apiJson<Category | { category?: Category }>(createEndpoint, {
                method: "POST",
                body: JSON.stringify({ name: name.trim() }),
            });

            const created = ("category" in res ? res.category : res) as Category | undefined;

            if (created?.id) {
                onCreated?.(created);
                onClose();
                return;
            }

            setErrorGeneral("カテゴリー作成に成功しましたが、返却形式が想定と異なります。");
        } catch (err: any) {
            const data = (err?.data ?? null) as LaravelValidationError | null;

            const nameMsg = data?.errors?.name?.[0];
            if (nameMsg) setErrorName(nameMsg);

            const msg =
                data?.message ||
                (data?.errors ? Object.values(data.errors).flat().join("\n") : "") ||
                err?.message ||
                "作成に失敗しました。";

            if (!nameMsg) setErrorGeneral(msg);
        } finally {
            setLoading(false);
        }
    };

    if (!mounted) return null;

    return createPortal(
        <AnimatePresence>
            {isOpen && (
                <>
                    <motion.div
                        className="category-modal-overlay"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                    />

                    <motion.div
                        className="category-modal-wrapper"
                        initial={{ opacity: 0, scale: 0.9, y: -30 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: -30 }}
                        transition={{ duration: 0.25 }}
                    >
                        <div
                            className="category-modal-content"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="category-modal-title"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <h2 id="category-modal-title" className="category-modal-title">
                                新規カテゴリー作成
                            </h2>

                            {errorGeneral && (
                                <p className="category-modal-error" style={{ whiteSpace: "pre-wrap" }}>
                                    {errorGeneral}
                                </p>
                            )}

                            <form onSubmit={handleSubmit} className="category-modal-form" noValidate>
                                <div className="category-modal-field">
                                    <label htmlFor="category-name" className="category-modal-label">
                                        カテゴリー名
                                    </label>

                                    <input
                                        ref={inputRef}
                                        id="category-name"
                                        type="text"
                                        value={name}
                                        onChange={(e) => {
                                            setName(e.target.value);
                                            if (errorName) setErrorName("");
                                            if (errorGeneral) setErrorGeneral("");
                                        }}
                                        className="category-modal-input"
                                        placeholder="例: CERAMIC COATING"
                                        disabled={loading}
                                    />

                                    {errorName && <p className="category-modal-error">{errorName}</p>}
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
                                        disabled={!canSubmit}
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
        </AnimatePresence>,
        document.body
    );
}
