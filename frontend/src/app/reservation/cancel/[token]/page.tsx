"use client";

import type React from "react";
import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import styles from "./cancel.module.css";

import { apiJson } from "@/lib/apiFetch";

type ReservationSummary = {
    id: number;
    name: string;
    date: string; // YYYY-MM-DD
    start_time: string | null; // HH:mm
    end_time: string | null; // HH:mm
    notes: string | null;
    service_name: string | null;
    status: string | null;
};

type ShowOk = {
    token_valid: true;
    reservation: ReservationSummary;
};

type ShowNg = {
    token_valid?: boolean;
    message: string;
};

export default function CancelConfirmPage() {
    const params = useParams<{ token: string | string[] }>();
    const rawToken = params?.token;
    const token = Array.isArray(rawToken) ? rawToken[0] : rawToken;
    const router = useRouter();

    const [loading, setLoading] = useState(true);
    const [reservation, setReservation] = useState<ReservationSummary | null>(null);
    const [pageError, setPageError] = useState<string>("");

    const [cancelReason, setCancelReason] = useState("");
    const [submitLoading, setSubmitLoading] = useState(false);
    const [submitError, setSubmitError] = useState<string>("");

    const canSubmit = useMemo(() => !submitLoading, [submitLoading]);

    useEffect(() => {
        if (!token) return;

        let cancelled = false;

        (async () => {
            setLoading(true);
            setPageError("");
            setReservation(null);

            try {
                const data = await apiJson<ShowOk>(`/api/reservations/cancel/${token}`, { method: "GET" });
                if (cancelled) return;

                setReservation(data?.reservation ?? null);
            } catch (e: any) {
                if (cancelled) return;

                const msg =
                    e?.data?.message ||
                    e?.message ||
                    "キャンセル情報の取得に失敗しました。URLをご確認ください。";

                setPageError(msg);
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [token]);

    const onSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!token || !canSubmit) return;

        setSubmitLoading(true);
        setSubmitError("");

        try {
            const res = await apiJson<{ ok: boolean; message: string; errors?: Record<string, string[]> }>(
                `/api/reservations/cancel/${token}`,
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        cancel_reason: cancelReason.trim() === "" ? null : cancelReason.trim(),
                    }),
                }
            );

            // result へ（query に詰めて表示）
            const ok = res?.ok ? "1" : "0";
            const message = encodeURIComponent(res?.message ?? "");
            router.push(`/reservation/cancel/${token}/result?ok=${ok}&message=${message}`);
        } catch (err: any) {
            const data = err?.data ?? null;

            const msg =
                data?.message ||
                (data?.errors ? Object.values(data.errors).flat().join("\n") : "") ||
                err?.message ||
                "キャンセル処理に失敗しました。";

            setSubmitError(msg);
        } finally {
            setSubmitLoading(false);
        }
    };

    if (loading) {
        return (
            <div className={styles.wrap}>
                <div className={styles.card}>
                    <div className={styles.cardHeader}>
                        <h1 className={styles.title}>読み込み中...</h1>
                        <p className={styles.subtitle}>キャンセル情報を取得しています。</p>
                    </div>
                    <div className={styles.cardBody} />
                </div>
            </div>
        );
    }

    // 取得できない（無効/期限切れ/Next側の404等）
    if (pageError || !reservation) {
        return (
            <div className={styles.wrap}>
                <div className={styles.card}>
                    <div className={styles.cardHeader}>
                        <h1 className={styles.title}>キャンセルページを表示できません</h1>
                        <p className={styles.subtitle}>URL が無効、または期限切れの可能性があります。</p>
                    </div>
                    <div className={styles.cardBody}>
                        <p className={styles.errorBox} style={{ whiteSpace: "pre-wrap" }}>
                            {pageError || "予約情報が取得できませんでした。"}
                        </p>

                        <div className={styles.actions}>
                            <Link href="/" className={`${styles.btn} ${styles.btnSecondary}`}>
                                トップへ戻る
                            </Link>
                            <Link href="/reservation" className={`${styles.btn} ${styles.btnSecondary}`}>
                                予約ページへ
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    const dateLine = `${reservation.date} ${reservation.start_time ?? ""}${reservation.end_time ? ` 〜 ${reservation.end_time}` : ""
        }`;

    return (
        <div className={styles.wrap}>
            <div className={styles.card}>
                <div className={styles.cardHeader}>
                    <h1 className={styles.title}>予約をキャンセルしますか？</h1>
                    <p className={styles.subtitle}>
                        以下の予約内容をご確認のうえ、キャンセル理由（任意）を入力して「キャンセルを確定する」を押してください。
                    </p>
                </div>

                <div className={styles.cardBody}>
                    <div className={styles.summary} aria-label="予約内容">
                        <div className={styles.summaryRow}>
                            <div className={styles.summaryLabel}>お名前</div>
                            <div className={styles.summaryValue}>{reservation.name}</div>
                        </div>

                        <div className={styles.summaryRow}>
                            <div className={styles.summaryLabel}>日時</div>
                            <div className={styles.summaryValue}>{dateLine}</div>
                        </div>

                        <div className={styles.summaryRow}>
                            <div className={styles.summaryLabel}>メニュー</div>
                            <div className={styles.summaryValue}>{reservation.service_name ?? "未設定"}</div>
                        </div>

                        <div className={styles.summaryRow}>
                            <div className={styles.summaryLabel}>備考</div>
                            <div className={styles.summaryValue}>{reservation.notes ? reservation.notes : "なし"}</div>
                        </div>
                    </div>

                    <div className={styles.note}>
                        ※ 誤操作防止のため、このページを開いただけではキャンセルは確定しません。
                        <br />
                        「キャンセルを確定する」を押すと、予約がキャンセルされます。
                    </div>

                    {submitError && (
                        <div className={styles.errorBox} style={{ whiteSpace: "pre-wrap", marginTop: 14 }}>
                            {submitError}
                        </div>
                    )}

                    <form onSubmit={onSubmit}>
                        <div className={styles.formGroup}>
                            <label htmlFor="cancel_reason" className={styles.label}>
                                キャンセル理由（任意）
                            </label>

                            <textarea
                                id="cancel_reason"
                                name="cancel_reason"
                                rows={4}
                                maxLength={500}
                                className={styles.textarea}
                                placeholder="例）急な予定が入り来店できなくなったため"
                                value={cancelReason}
                                onChange={(e) => setCancelReason(e.target.value)}
                                disabled={submitLoading}
                                // ✅ CSS側で pointer-events が殺されていても入力できるように保険
                                style={{ pointerEvents: "auto" }}
                            />

                            <div className={styles.hint}>最大500文字まで入力できます。入力がなくてもキャンセルできます。</div>
                        </div>

                        <div className={styles.actions}>
                            <button type="submit" className={`${styles.btn} ${styles.btnDanger}`} disabled={!canSubmit}>
                                {submitLoading ? "処理中..." : "キャンセルを確定する"}
                            </button>

                            <Link href="/" className={`${styles.btn} ${styles.btnSecondary}`}>
                                キャンセルしない（トップへ戻る）
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
