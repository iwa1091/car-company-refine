"use client";

import { useMemo } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import styles from "../cancel.module.css";


export default function CancelResultPage() {
    const sp = useSearchParams();

    const ok = sp.get("ok") === "1";
    const message = useMemo(() => {
        const raw = sp.get("message") ?? "";
        try {
            return decodeURIComponent(raw);
        } catch {
            return raw;
        }
    }, [sp]);

    return (
        <div className={styles.wrap}>
            <div className={styles.card}>
                <div className={styles.resultHeader}>
                    {ok ? (
                        <>
                            <div className={`${styles.badge} ${styles.badgeOk}`}>✅ 完了</div>
                            <h1 className={styles.resultTitle}>キャンセルが完了しました</h1>
                        </>
                    ) : (
                        <>
                            <div className={`${styles.badge} ${styles.badgeNg}`}>⚠️ エラー</div>
                            <h1 className={styles.resultTitle}>キャンセルを実行できませんでした</h1>
                        </>
                    )}
                </div>

                <div className={styles.resultBody}>
                    <p className={styles.message} style={{ whiteSpace: "pre-wrap" }}>
                        {message || (ok ? "キャンセルが完了しました。" : "キャンセルを実行できませんでした。")}
                    </p>

                    <p className={styles.muted}>ご不明点がある場合は、管理者へお問い合わせください。</p>

                    <div className={styles.actions}>
                        <Link href="/" className={`${styles.btn} ${styles.btnPrimary}`}>
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
