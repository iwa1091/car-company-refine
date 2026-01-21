"use client";

// /home/ri309/new-app/frontend/src/app/admin/business-hours/page.tsx
import { useEffect, useMemo, useState } from "react";
import Link from "next/link";

/**
 * =========================
 * Types
 * =========================
 */
type BusinessHourWeekly = {
    week_of_month: number | string;
    day_of_week: string; // "日".."土"
    is_closed: boolean | number | string;
    open_time: string | null;
    close_time: string | null;
};

type ApiErrorShape = {
    message?: string;
    errors?: Record<string, string[]>;
};

/**
 * =========================
 * CSRF helpers (meta + cookie 対応)
 * =========================
 */
function getCsrfTokenFromMeta(): string {
    if (typeof document === "undefined") return "";
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || ""
    );
}

function getCookie(name: string): string {
    if (typeof document === "undefined") return "";
    const m = document.cookie.match(new RegExp("(^|;\\s*)" + name + "=([^;]*)"));
    return m ? decodeURIComponent(m[2]) : "";
}

/**
 * Laravel標準（XSRF-TOKEN cookie）に寄せる
 * - X-XSRF-TOKEN には「URL decode済み」の token を入れる
 */
function buildCsrfHeaders(method: string): Record<string, string> {
    const m = method.toUpperCase();
    const needs = ["POST", "PUT", "PATCH", "DELETE"].includes(m);
    if (!needs) return {};

    const meta = getCsrfTokenFromMeta();
    const xsrf = getCookie("XSRF-TOKEN");

    return {
        ...(meta ? { "X-CSRF-TOKEN": meta } : {}),
        ...(xsrf ? { "X-XSRF-TOKEN": xsrf } : {}),
    };
}

/**
 * =========================
 * API fetch（web middleware + same-origin セッション想定）
 * =========================
 */
async function apiFetch<T>(
    url: string,
    options: RequestInit = {}
): Promise<{ ok: true; data: T } | { ok: false; status: number; data: ApiErrorShape | null }> {
    const method = (options.method || "GET").toString().toUpperCase();

    const headers: HeadersInit = {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...(options.headers || {}),
        ...buildCsrfHeaders(method),
    };

    const res = await fetch(url, {
        credentials: "same-origin",
        ...options,
        headers,
    });

    if (!res.ok) {
        const json = (await res.json().catch(() => null)) as ApiErrorShape | null;
        return { ok: false, status: res.status, data: json };
    }

    const data = (await res.json().catch(() => null)) as T;
    return { ok: true, data };
}

/**
 * =========================
 * Page
 * =========================
 */
export default function AdminBusinessHoursPage() {
    const [hours, setHours] = useState<BusinessHourWeekly[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [message, setMessage] = useState<string>("");

    // 今月 / 来月 を想定（Laravel版の挙動に寄せる）
    const now = useMemo(() => new Date(), []);
    const [selectedMonth, setSelectedMonth] = useState<number>(now.getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState<number>(now.getFullYear());
    const [selectedWeek, setSelectedWeek] = useState<number>(1);

    /**
     * 営業時間を取得
     */
    const fetchWeeklyHours = async (year: number, month: number, signal: AbortSignal) => {
        setLoading(true);
        setMessage("");

        try {
            const result = await apiFetch<BusinessHourWeekly[]>(
                `/api/business-hours/weekly?year=${year}&month=${month}`,
                { method: "GET", signal }
            );

            if (!result.ok) {
                setHours([]);
                setMessage("営業時間の取得に失敗しました。");
                return;
            }

            setHours(Array.isArray(result.data) ? result.data : []);
        } catch (err: any) {
            if (err?.name === "AbortError") return;
            console.error("営業時間取得失敗:", err);
            setMessage("営業時間の取得に失敗しました。");
            setHours([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const ac = new AbortController();
        fetchWeeklyHours(selectedYear, selectedMonth, ac.signal);
        return () => ac.abort();
    }, [selectedYear, selectedMonth]);

    /**
     * 値の変更
     */
    const handleChange = (
        index: number,
        field: keyof BusinessHourWeekly,
        value: BusinessHourWeekly[keyof BusinessHourWeekly]
    ) => {
        setHours((prev) => {
            const updated = [...prev];
            const row = { ...updated[index] };
            (row as any)[field] = value;

            // 休業日にしたら時間をクリア
            if (field === "is_closed" && value === true) {
                row.open_time = null;
                row.close_time = null;
            }

            updated[index] = row;
            return updated;
        });
    };

    /**
     * 保存（PUT）
     */
    const handleSave = async () => {
        setMessage("");

        try {
            const result = await apiFetch<unknown>("/api/business-hours/weekly", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(hours),
            });

            if (result.ok) {
                setMessage("営業時間を更新しました。");
                window.setTimeout(() => setMessage(""), 3000);
                return;
            }

            const json = result.data;
            const msg =
                json?.message ||
                (json?.errors ? Object.values(json.errors).flat().join("\n") : "") ||
                "更新に失敗しました。";
            setMessage(msg);
        } catch (err) {
            console.error("更新エラー:", err);
            setMessage("サーバー通信エラーが発生しました。");
        }
    };

    /**
     * 表示する週データをフィルタ
     * - week_of_month が "1" のような文字列で来ても動くように Number 比較
     */
    const filteredHours = useMemo(() => {
        return hours.filter((h) => Number(h.week_of_month) === Number(selectedWeek));
    }, [hours, selectedWeek]);

    /**
     * 月のプルダウン（今月・来月）
     * ※ 元コードのまま。ただし12月→来月が1月の時、年がズレる問題は “改善余地” あり
     */
    const months = useMemo(() => {
        const thisMonth = new Date().getMonth() + 1;
        const nextMonth = thisMonth + 1 > 12 ? 1 : thisMonth + 1;

        return [
            { label: "今月", value: thisMonth },
            { label: "来月", value: nextMonth },
        ];
    }, []);

    if (loading) {
        return (
            <div className="admin-business-hours-page">
                <div className="admin-business-hours-container">
                    <p className="business-hours-loading">読み込み中...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="admin-business-hours-page">
            <div className="admin-business-hours-container">
                {/* 🔙 戻る（NextのURLへ） */}
                <div className="business-hours-back-area">
                    <Link href="/admin" className="business-hours-back-button">
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="business-hours-title">
                    営業日・営業時間設定（週単位・15分刻み）
                </h1>

                {message && <p className="business-hours-message">{message}</p>}

                {/* 月・週セレクト */}
                <div className="business-hours-controls">
                    <select
                        value={selectedMonth}
                        onChange={(e) => setSelectedMonth(Number(e.target.value))}
                        className="business-hours-month-select"
                    >
                        {months.map((m) => (
                            <option key={m.value} value={m.value}>
                                {selectedYear}年 {m.value}月（{m.label}）
                            </option>
                        ))}
                    </select>

                    <div className="business-hours-week-tabs">
                        {[1, 2, 3, 4, 5].map((week) => (
                            <button
                                key={week}
                                type="button"
                                onClick={() => setSelectedWeek(week)}
                                className={
                                    "business-hours-week-button" +
                                    (selectedWeek === week ? " business-hours-week-button--active" : "")
                                }
                            >
                                第{week}週
                            </button>
                        ))}
                    </div>
                </div>

                {/* テーブル */}
                <div className="business-hours-table-wrapper">
                    <table className="business-hours-table">
                        <thead>
                            <tr>
                                <th>曜日</th>
                                <th>開店時間</th>
                                <th>閉店時間</th>
                                <th>休業日</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredHours.map((h) => {
                                const idx = hours.indexOf(h); // 元実装に寄せる（参照一致前提）

                                return (
                                    <tr key={`${h.day_of_week}-${h.week_of_month}`}>
                                        <td className="business-hours-day-cell">{h.day_of_week}</td>
                                        <td>
                                            <input
                                                type="time"
                                                step="900"
                                                value={h.open_time || ""}
                                                onChange={(e) => handleChange(idx, "open_time", e.target.value)}
                                                disabled={!!h.is_closed}
                                                className="business-hours-time-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                step="900"
                                                value={h.close_time || ""}
                                                onChange={(e) => handleChange(idx, "close_time", e.target.value)}
                                                disabled={!!h.is_closed}
                                                className="business-hours-time-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="checkbox"
                                                checked={!!h.is_closed}
                                                onChange={(e) => handleChange(idx, "is_closed", e.target.checked)}
                                                className="business-hours-closed-checkbox"
                                            />
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {/* 保存ボタン */}
                <div className="business-hours-save-area">
                    <button type="button" onClick={handleSave} className="business-hours-save-button">
                        保存する
                    </button>
                </div>
            </div>
        </div>
    );
}
