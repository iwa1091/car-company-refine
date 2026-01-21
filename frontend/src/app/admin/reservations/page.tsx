// /home/ri309/new-app/frontend/src/app/admin/reservations/page.tsx
"use client";

import type React from "react";
import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

import { apiJson } from "@/lib/apiFetch";

/** utils（Laravel版と同等） */
function pad2(n: number): string {
    return String(n).padStart(2, "0");
}
function toYmd(date: Date): string {
    const y = date.getFullYear();
    const m = pad2(date.getMonth() + 1);
    const d = pad2(date.getDate());
    return `${y}-${m}-${d}`;
}
function startOfMonth(year: number, month: number): Date {
    return new Date(year, month - 1, 1);
}
function endOfMonth(year: number, month: number): Date {
    return new Date(year, month, 0);
}
function getWeekOfMonth(dateObj: Date): number {
    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    // ISO風（Mon=1..Sun=7）へ
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay();
    return Math.ceil((dateObj.getDate() + firstIso - 1) / 7);
}
function dayJp(dateObj: Date): "日" | "月" | "火" | "水" | "木" | "金" | "土" {
    const names = ["日", "月", "火", "水", "木", "金", "土"] as const;
    return names[dateObj.getDay()];
}

/** ✅ Hydration対策：月表示/曜日表示をサーバー/クライアントで一致させる */
function formatMonthYearJp(date: Date): string {
    // "2026年1月" 形式（SSR/CSRで常に同じ）
    const y = date.getFullYear();
    const m = date.getMonth() + 1;
    return `${y}年${m}月`;
}
function formatShortWeekdayJp(_locale: string | undefined, date: Date): string {
    // "日"〜"土"
    return dayJp(date);
}

/** ✅ キャンセル除外判定（timetable と同系統で安全に） */
type AnyObj = Record<string, any>;

function isTruthy(v: unknown): boolean {
    if (typeof v === "boolean") return v;
    if (typeof v === "number") return v !== 0;
    if (typeof v === "string") {
        const s = v.trim().toLowerCase();
        return s === "1" || s === "true" || s === "yes";
    }
    return false;
}

function isCanceledReservationLike(r: AnyObj): boolean {
    const st = r?.status;
    if (st != null) {
        const s = String(st).trim().toLowerCase();
        if (
            s === "canceled" ||
            s === "cancelled" ||
            s === "cancel" ||
            s === "canceled_by_user" ||
            s === "cancelled_by_user" ||
            s === "canceled_by_admin" ||
            s === "cancelled_by_admin"
        ) {
            return true;
        }
        if (s.includes("cancel")) return true;
    }

    if (r?.canceled_at) return true;
    if (r?.deleted_at) return true;
    if (isTruthy(r?.is_canceled) || isTruthy(r?.is_cancelled)) return true;

    return false;
}

/** API型（必要最小限） */
type BusinessHour = {
    week_of_month: number | string;
    day_of_week: "日" | "月" | "火" | "水" | "木" | "金" | "土" | string;
    is_closed: boolean | number | string;
};

type Reservation = {
    date: string; // "YYYY-MM-DD ..." を想定（slice(0,10)する）

    // ✅ 追加：APIが返している場合はキャンセル判定に使える（返してなくてもOK）
    status?: string | null;
    canceled_at?: string | null;
    deleted_at?: string | null;
    is_canceled?: boolean | number | string | null;
    is_cancelled?: boolean | number | string | null;
};

type CountsByDate = Record<string, number>;

type CalendarView = "month" | "year" | "decade" | "century";

type TileArgs = {
    activeStartDate: Date;
    date: Date;
    view: CalendarView;
};

/** react-calendar（型の取り回しを簡単にするため最小でimport） */
import Calendar from "react-calendar";

export default function AdminReservationCalendarPage() {
    const router = useRouter();

    const [monthOffset, setMonthOffset] = useState<number>(0); // 0=今月, 1=来月

    // ✅ 画面復帰（戻る/別画面から戻る等）で再取得させるためのキー
    const [reloadKey, setReloadKey] = useState<number>(0);

    const base = useMemo<Date>(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth() + monthOffset, 1);
    }, [monthOffset]);

    const year = base.getFullYear();
    const month = base.getMonth() + 1;

    const [businessHours, setBusinessHours] = useState<BusinessHour[]>([]);
    const [countsByDate, setCountsByDate] = useState<CountsByDate>({});
    const [loading, setLoading] = useState<boolean>(true);

    // ✅ フォーカス復帰 / タブ復帰 で再取得（表示の取り残し防止）
    useEffect(() => {
        const onFocus = () => setReloadKey((k) => k + 1);
        const onVisible = () => {
            if (document.visibilityState === "visible") {
                setReloadKey((k) => k + 1);
            }
        };

        window.addEventListener("focus", onFocus);
        document.addEventListener("visibilitychange", onVisible);

        return () => {
            window.removeEventListener("focus", onFocus);
            document.removeEventListener("visibilitychange", onVisible);
        };
    }, []);

    // 営業時間（その月）
    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const data = await apiJson<BusinessHour[]>(
                    `/api/business-hours/weekly?year=${year}&month=${month}`,
                    { method: "GET" }
                );
                if (!cancelled) setBusinessHours(Array.isArray(data) ? data : []);
            } catch {
                if (!cancelled) setBusinessHours([]);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [year, month, reloadKey]);

    // 予約件数（その月：from/to で軽量に）
    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoading(true);
            try {
                const from = toYmd(startOfMonth(year, month));
                const to = toYmd(endOfMonth(year, month));

                const data = await apiJson<Reservation[]>(
                    `/api/admin/reservations?from=${from}&to=${to}`,
                    { method: "GET" }
                );

                const map: CountsByDate = {};
                for (const r of Array.isArray(data) ? data : []) {
                    // ✅ キャンセル済みはカウントしない（ドットが残る原因を潰す）
                    if (isCanceledReservationLike(r as AnyObj)) continue;

                    const d = String(r.date).slice(0, 10);
                    map[d] = (map[d] || 0) + 1;
                }

                if (!cancelled) setCountsByDate(map);
            } catch {
                if (!cancelled) setCountsByDate({});
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [year, month, reloadKey]);

    const tileDisabled = ({ date, view }: TileArgs): boolean => {
        if (view !== "month") return false;

        const w = getWeekOfMonth(date);
        const d = dayJp(date);

        const target = businessHours.find(
            (b) => Number(b.week_of_month) === Number(w) && String(b.day_of_week) === d
        );

        // データが無い月は「全部押せる」にしておく（seedされる想定）
        if (!target) return false;

        // is_closed の表現揺れに強くする
        const v = target.is_closed;
        return v === true || v === 1 || v === "1" || v === "true";
    };

    const tileContent = ({ date, view }: TileArgs): React.ReactNode => {
        if (view !== "month") return null;

        const key = toYmd(date);
        const c = countsByDate[key] || 0;
        if (!c) return null;

        return <span className="admin-cal-dot" title={`${c}件`} />;
    };

    const onClickDay = (date: Date) => {
        const ymd = toYmd(date);
        // ✅ Inertia route("admin.timetable", { date }) の代替（Next側URL）
        router.push(`/admin/timetable?date=${encodeURIComponent(ymd)}`);
    };

    return (
        <div className="admin-reservation-page">
            <div className="admin-reservation-back">
                <Link href="/admin" className="admin-reservation-back-link">
                    前のページに戻る
                </Link>
            </div>

            <h1 className="admin-reservation-title">予約カレンダー</h1>

            <div className="admin-cal-header">
                <div className="admin-cal-tabs">
                    <button
                        type="button"
                        className={"admin-cal-tab " + (monthOffset === 0 ? "is-active" : "")}
                        onClick={() => setMonthOffset(0)}
                    >
                        今月
                    </button>
                    <button
                        type="button"
                        className={"admin-cal-tab " + (monthOffset === 1 ? "is-active" : "")}
                        onClick={() => setMonthOffset(1)}
                    >
                        来月
                    </button>
                </div>

                <div className="admin-cal-note">
                    ● は予約あり（件数はツールチップ）
                    {loading ? <span style={{ marginLeft: "0.5rem" }}>（読み込み中…）</span> : null}
                </div>
            </div>

            <div className="admin-cal-wrap">
                <Calendar
                    activeStartDate={base}
                    value={null}
                    onClickDay={onClickDay}
                    tileDisabled={tileDisabled}
                    tileContent={tileContent}
                    showNeighboringMonth={true}
                    prevLabel={null}
                    nextLabel={null}
                    prev2Label={null}
                    next2Label={null}
                    /** ✅ Hydration対策：表示フォーマットを固定 */
                    locale="ja-JP"
                    formatMonthYear={((_locale: string | undefined, date: Date) => formatMonthYearJp(date)) as any}
                />
            </div>
        </div>
    );
}
