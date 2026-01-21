"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { apiJson } from "../../../lib/apiFetch";

type MeResponse = {
    id: number;
    name: string;
    email: string;
};

export default function AdminLoginPage() {
    const router = useRouter();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");

    const [submitting, setSubmitting] = useState(false);
    const [status, setStatus] = useState<string>("");
    const [error, setError] = useState<string>("");

    // すでにログイン済みなら /admin へ
    useEffect(() => {
        let mounted = true;
        (async () => {
            try {
                await apiJson<MeResponse>("/api/admin/me", { method: "GET" });
                if (!mounted) return;
                router.replace("/admin");
            } catch {
                // 未ログインは正常（何もしない）
            }
        })();
        return () => {
            mounted = false;
        };
    }, [router]);

    const onSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (submitting) return;

        setSubmitting(true);
        setStatus("");
        setError("");

        try {
            await apiJson<{ ok: true }>("/api/admin/session", {
                method: "POST",
                body: JSON.stringify({
                    email,
                    password,
                }),
            });

            setStatus("ログインしました。");
            router.replace("/admin");
        } catch (err: any) {
            // apiJson は message を Error(message) で投げる実装
            const msg = err?.message || "ログインに失敗しました。";
            setError(msg);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="admin-login">
            <div className="admin-login__bg" aria-hidden="true" />

            <div className="admin-login__card">
                <div className="admin-login__head">
                    <div className="admin-login__badge">REFINE</div>
                    <h1 className="admin-login__title">管理者ログイン</h1>
                    <p className="admin-login__subtitle">
                        管理画面へアクセスするにはログインしてください
                    </p>
                </div>

                {status ? (
                    <div
                        className="admin-login__alert admin-login__alert--info"
                        role="status"
                    >
                        {status}
                    </div>
                ) : null}

                {error ? (
                    <div
                        className="admin-login__alert admin-login__alert--error"
                        role="alert"
                    >
                        <p className="admin-login__alert-title">入力内容をご確認ください</p>
                        <ul className="admin-login__alert-list">
                            <li>{error}</li>
                        </ul>
                    </div>
                ) : null}

                <form className="admin-login__form" onSubmit={onSubmit} noValidate>
                    <div className="admin-login__field">
                        <label htmlFor="email" className="admin-login__label">
                            メールアドレス
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autoComplete="email"
                            required
                            className={"admin-login__input"}
                            placeholder="admin@example.com"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            disabled={submitting}
                        />
                    </div>

                    <div className="admin-login__field">
                        <label htmlFor="password" className="admin-login__label">
                            パスワード
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            required
                            className={"admin-login__input"}
                            placeholder="••••••••"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            disabled={submitting}
                        />
                    </div>

                    <button
                        type="submit"
                        className="admin-login__button"
                        disabled={submitting}
                    >
                        {submitting ? "ログイン中..." : "ログイン"}
                    </button>
                </form>
            </div>
        </div>
    );
}
