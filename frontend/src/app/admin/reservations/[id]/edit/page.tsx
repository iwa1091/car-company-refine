// /home/ri309/new-app/frontend/src/app/admin/reservations/[id]/edit/page.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import Calendar from "react-calendar";

import { apiJson } from "@/lib/apiFetch";

/**
 * ✅ "HH:mm" に正規化（Date変換はしない）
 */
function normalizeHHmm(value: unknown): string {
    if (!value) return "";

    const str = String(value).trim();

    // "HH:MM" / "HH:MM:SS"
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    // ISO / datetime文字列から "HH:MM" 抽出
    const m = str.match(/\b(\d{2}:\d{2})(?::\d{2})?\b/);
    if (m) return m[1];

    return "";
}

/**
 * ✅ 日付を "YYYY-MM-DD" に正規化
 */
function normalizeYmd(value: unknown): string {
    if (!value) return "";
    const str = String(value).trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;

    // "2025-12-29T..." など
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) return str.slice(0, 10);

    return "";
}

/**
 * ✅ "YYYY-MM-DD" をローカル日付として安全に Date 化（カレンダー用）
 */
function safeDateFromYmd(value: unknown): Date | null {
    const ymd = normalizeYmd(value);
    if (!ymd) return null;

    const [y, m, d] = ymd.split("-").map(Number);
    return new Date(y, m - 1, d);
}

/**
 * ✅ week_of_month をJSで計算（PHP側 BusinessHour::getWeekOfMonth に合わせる）
 */
function getWeekOfMonth(dateObj: Date): number {
    if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return 1;

    const day = dateObj.getDate();
    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay(); // Sun(0)→7

    return Math.ceil((day + firstIso - 1) / 7);
}

/**
 * 📅「YYYY年MM月DD日 HH:mm」表示用
 */
function formatDateTimeJp(ymd: string, timeHHmm: string): string {
    const dateStr = normalizeYmd(ymd);
    if (!dateStr) return "";

    const [y, m, d] = dateStr.split("-");
    const time = normalizeHHmm(timeHHmm) || "--:--";
    return `${y}年${m}月${d}日 ${time}`;
}

function minutesToHHmm(totalMinutes: number): string {
    const h = String(Math.floor(totalMinutes / 60)).padStart(2, "0");
    const m = String(totalMinutes % 60).padStart(2, "0");
    return `${h}:${m}`;
}

function hhmmToMinutes(hhmm: string): number | null {
    const t = normalizeHHmm(hhmm);
    if (!t) return null;
    const [h, m] = t.split(":").map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    return h * 60 + m;
}

/**
 * ✅ react-calendar の formatMonthYear / formatShortWeekday は
 *    (locale, date) で呼ばれることがあるため、両対応にして落ちないようにする
 */
const WEEKDAYS_JP = ["日", "月", "火", "水", "木", "金", "土"] as const;

const formatMonthYearJp = (a: any, b?: any) => {
    const date: Date = b instanceof Date ? b : a;
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";
    return `${date.getFullYear()}年${date.getMonth() + 1}月`;
};

const formatShortWeekdayJp = (a: any, b?: any) => {
    const date: Date = b instanceof Date ? b : a;
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";
    return WEEKDAYS_JP[date.getDay()];
};

/** =========================
 *  型（必要最低限）
 ========================= */

type ReservationService = {
    duration_minutes?: number;
};

type Reservation = {
    id: number;
    name?: string;
    date?: string;
    start_time?: string;
    end_time?: string;
    service_id?: number | null;
    service?: ReservationService | null;
};

type BusinessHourWeekly = {
    week_of_month: number | string;
    day_of_week: string; // "日".."土"
    is_closed?: boolean | number;
    open_time?: string;
    close_time?: string;
};

type CalendarTileArgs = { date: Date; view: string };

export default function AdminReservationEditPage() {
    const router = useRouter();
    const params = useParams<{ id: string }>();

    const reservationId = Number(params?.id);

    const [loading, setLoading] = useState(true);
    const [pageError, setPageError] = useState("");

    const [reservation, setReservation] = useState<Reservation | null>(null);

    // 表示中の月（営業時間取得用）
    const [activeYear, setActiveYear] = useState<number>(new Date().getFullYear());
    const [activeMonth, setActiveMonth] = useState<number>(new Date().getMonth() + 1);

    const [businessHours, setBusinessHours] = useState<BusinessHourWeekly[]>([]);
    const [availableTimes, setAvailableTimes] = useState<string[]>([]);

    const [formData, setFormData] = useState<{
        name: string;
        date: string;
        start_time: string;
        end_time: string;
        service_id: number | null;
        service_duration: number;
    }>({
        name: "",
        date: "",
        start_time: "",
        end_time: "",
        service_id: null,
        service_duration: 0,
    });

    const [submitError, setSubmitError] = useState("");

    /**
     * ✅ 予約データ取得（Nextでは props が無いので API から取る）
     */
    useEffect(() => {
        if (!reservationId || Number.isNaN(reservationId)) {
            setLoading(false);
            setPageError("予約IDが不正です。URLを確認してください。");
            return;
        }

        const loadReservation = async () => {
            setLoading(true);
            setPageError("");

            try {
                // ✅ あなたのAPIに合わせて調整するならここだけ
                const data = await apiJson<{ reservation: Reservation } | Reservation>(
                    `/api/admin/reservations/${reservationId}`,
                    { method: "GET" }
                );

                const resv: Reservation =
                    (data as any)?.reservation ? (data as any).reservation : (data as any);

                setReservation(resv);

                const initialDate = normalizeYmd(resv?.date);
                const initialStart = normalizeHHmm(resv?.start_time);
                const initialDuration = Number(resv?.service?.duration_minutes || 0);

                // 表示月を合わせる（営業時間取得用）
                const initialDateObj = safeDateFromYmd(initialDate) || new Date();
                setActiveYear(initialDateObj.getFullYear());
                setActiveMonth(initialDateObj.getMonth() + 1);

                // end_time は持っていれば正規化、無ければ duration から計算（保険）
                const rawEnd = resv?.end_time;
                let end = normalizeHHmm(rawEnd);

                if (!end && initialStart && initialDuration > 0) {
                    const startMin = hhmmToMinutes(initialStart);
                    if (startMin !== null) {
                        end = minutesToHHmm(startMin + initialDuration);
                    }
                }

                setFormData({
                    name: resv?.name || "",
                    date: initialDate,
                    start_time: initialStart,
                    end_time: end,
                    service_id: resv?.service_id ?? null,
                    service_duration: initialDuration,
                });
            } catch (e: any) {
                const status = e?.status;
                if (status === 401) {
                    router.replace("/admin/login");
                    return;
                }
                setPageError("予約データの取得に失敗しました。API/認証を確認してください。");
            } finally {
                setLoading(false);
            }
        };

        loadReservation();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [reservationId]);

    /**
     * ✅ 週単位の営業時間を取得（/api/business-hours/weekly）
     */
    useEffect(() => {
        const fetchBusinessHoursWeekly = async () => {
            try {
                const res = await fetch(
                    `/api/business-hours/weekly?year=${activeYear}&month=${activeMonth}`,
                    { credentials: "same-origin" }
                );
                if (res.ok) {
                    const data = await res.json();
                    setBusinessHours(Array.isArray(data) ? data : []);
                } else {
                    setBusinessHours([]);
                }
            } catch {
                setBusinessHours([]);
            }
        };

        fetchBusinessHoursWeekly();
    }, [activeYear, activeMonth]);

    /**
     * ✅ カレンダーの休業日グレーアウト（week_of_month 考慮）
     */
    const tileDisabled = ({ date }: CalendarTileArgs) => {
        // 表示中の月以外（前月/翌月のはみ出し日）は無効（安全）
        const tileYear = date.getFullYear();
        const tileMonth = date.getMonth() + 1;
        if (tileYear !== activeYear || tileMonth !== activeMonth) return true;

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayOfWeek = dayNames[date.getDay()];
        const weekOfMonth = getWeekOfMonth(date);

        const target = businessHours.find(
            (b) => Number(b.week_of_month) === Number(weekOfMonth) && b.day_of_week === dayOfWeek
        );

        return !target || !!target.is_closed;
    };

    /**
     * ✅ 選択日（date）に対して時間スロット生成（duration考慮）
     */
    useEffect(() => {
        const dateObj = safeDateFromYmd(formData.date);
        if (!dateObj) {
            setAvailableTimes([]);
            return;
        }
        if (businessHours.length === 0) {
            setAvailableTimes([]);
            return;
        }

        const duration = Number(formData.service_duration || 0);
        if (!duration || duration <= 0) {
            setAvailableTimes([]);
            return;
        }

        // 選択日が別月なら営業時間を取り直す
        const y = dateObj.getFullYear();
        const m = dateObj.getMonth() + 1;
        if (y !== activeYear || m !== activeMonth) {
            setActiveYear(y);
            setActiveMonth(m);
            return;
        }

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayOfWeek = dayNames[dateObj.getDay()];
        const weekOfMonth = getWeekOfMonth(dateObj);

        const target = businessHours.find(
            (b) => Number(b.week_of_month) === Number(weekOfMonth) && b.day_of_week === dayOfWeek
        );

        if (!target || target.is_closed) {
            setAvailableTimes([]);
            return;
        }

        const openHHmm = normalizeHHmm(target.open_time);
        const closeHHmm = normalizeHHmm(target.close_time);

        const openMin = hhmmToMinutes(openHHmm);
        const closeMin = hhmmToMinutes(closeHHmm);

        if (openMin === null || closeMin === null) {
            setAvailableTimes([]);
            return;
        }

        const lastStart = closeMin - duration;
        if (lastStart < openMin) {
            setAvailableTimes([]);
            return;
        }

        const slots: string[] = [];
        for (let t = openMin; t <= lastStart; t += 15) {
            slots.push(minutesToHHmm(t));
        }

        setAvailableTimes(slots);

        // 現在の start_time がスロット外ならクリア
        const current = normalizeHHmm(formData.start_time);
        if (current && !slots.includes(current)) {
            setFormData((prev) => ({ ...prev, start_time: "", end_time: "" }));
        }
    }, [formData.date, formData.service_duration, businessHours, activeYear, activeMonth, formData.start_time]);

    const handleChangeName = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData((prev) => ({ ...prev, name: e.target.value }));
    };

    const handleDateChange = (value: Date | Date[] | null) => {
        const date = Array.isArray(value) ? value[0] : value;
        if (!date) return;

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        setActiveYear(year);
        setActiveMonth(Number(month));

        setFormData((prev) => ({
            ...prev,
            date: `${year}-${month}-${day}`,
            start_time: "",
            end_time: "",
        }));
    };

    const handleActiveStartDateChange = ({ activeStartDate }: any) => {
        if (!activeStartDate) return;
        setActiveYear(activeStartDate.getFullYear());
        setActiveMonth(activeStartDate.getMonth() + 1);
    };

    const handlePickTime = (timeHHmm: string) => {
        const t = normalizeHHmm(timeHHmm);
        const duration = Number(formData.service_duration || 0);

        let end = "";
        const startMin = hhmmToMinutes(t);
        if (startMin !== null && duration > 0) {
            end = minutesToHHmm(startMin + duration);
        }

        setFormData((prev) => ({ ...prev, start_time: t, end_time: end }));
    };

    const calendarValue = useMemo(() => {
        return safeDateFromYmd(formData.date) || new Date();
    }, [formData.date]);

    const backHref = useMemo(() => {
        const ymd = normalizeYmd(formData.date);
        return ymd ? `/admin/timetable?date=${encodeURIComponent(ymd)}` : "/admin/reservations";
    }, [formData.date]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!reservationId) return;

        setSubmitError("");

        const payload = {
            ...formData,
            date: normalizeYmd(formData.date),
            start_time: normalizeHHmm(formData.start_time),
            end_time: normalizeHHmm(formData.end_time),
        };

        try {
            // ✅ あなたのAPIに合わせて調整するならここだけ
            await apiJson(`/api/admin/reservations/${reservationId}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });

            // 元の挙動に寄せて「タイムテーブルへ戻る（date付き）」
            router.replace(backHref);
        } catch (e: any) {
            const status = e?.status;
            if (status === 401) {
                router.replace("/admin/login");
                return;
            }
            const msg =
                e?.data?.message ||
                e?.message ||
                (e?.data?.errors ? Object.values(e.data.errors).flat().join("\n") : "") ||
                "更新に失敗しました。";
            setSubmitError(msg);
        }
    };

    if (loading) {
        return (
            <div className="admin-reservation-edit-page">
                <div className="admin-reservation-edit-card">
                    <h1 className="admin-reservation-edit-title">予約編集</h1>
                    <p className="admin-reservation-edit-time-empty">読み込み中...</p>
                </div>
            </div>
        );
    }

    if (pageError) {
        return (
            <div className="admin-reservation-edit-page">
                <div className="admin-reservation-edit-back">
                    <Link href="/admin" className="admin-reservation-edit-back-link">
                        ダッシュボードへ戻る
                    </Link>
                </div>

                <div className="admin-reservation-edit-card">
                    <h1 className="admin-reservation-edit-title">予約編集</h1>
                    <p className="admin-reservation-edit-time-empty">{pageError}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="admin-reservation-edit-page">
            {/* 🔙 戻る */}
            <div className="admin-reservation-edit-back">
                <Link href={backHref} className="admin-reservation-edit-back-link">
                    前のページに戻る
                </Link>
            </div>

            <div className="admin-reservation-edit-card">
                <h1 className="admin-reservation-edit-title">予約編集</h1>

                <form onSubmit={handleSubmit} className="admin-reservation-edit-form">
                    {/* 氏名 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">氏名</label>
                        <input
                            type="text"
                            name="name"
                            value={formData.name}
                            onChange={handleChangeName}
                            className="admin-reservation-edit-input"
                        />
                    </div>

                    {/* カレンダー */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">日付</label>
                        <div className="admin-reservation-edit-calendar-wrapper">
                            <div className="admin-reservation-edit-calendar">
                                <Calendar
                                    locale="ja-JP"
                                    formatMonthYear={formatMonthYearJp as any}
                                    formatShortWeekday={formatShortWeekdayJp as any}
                                    value={calendarValue}
                                    onChange={handleDateChange}
                                    onActiveStartDateChange={handleActiveStartDateChange}
                                    tileDisabled={tileDisabled}
                                />
                            </div>

                            <p className="admin-reservation-edit-date-text">
                                選択日: {formatDateTimeJp(formData.date, formData.start_time)}
                            </p>
                        </div>
                    </div>

                    {/* 時間 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">時間</label>
                        <div className="admin-reservation-edit-time-wrapper">
                            {availableTimes.length > 0 ? (
                                <div className="admin-reservation-edit-time-grid">
                                    {availableTimes.map((time) => {
                                        const normalized = normalizeHHmm(formData.start_time);
                                        const isSelected = normalized === time;

                                        return (
                                            <button
                                                key={time}
                                                type="button"
                                                onClick={() => handlePickTime(time)}
                                                className={`admin-reservation-edit-time-button ${isSelected ? "admin-reservation-edit-time-button--selected" : ""
                                                    }`}
                                            >
                                                {time}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="admin-reservation-edit-time-empty">営業時間外または休業日です</p>
                            )}
                        </div>
                    </div>

                    {/* 更新 */}
                    <button
                        type="submit"
                        className="admin-reservation-edit-submit"
                        disabled={!formData.date || !formData.start_time}
                        title={!formData.start_time ? "時間を選択してください" : ""}
                    >
                        更新
                    </button>

                    {/* エラー */}
                    {submitError ? <div className="admin-reservation-edit-error">{submitError}</div> : null}
                </form>
            </div>
        </div>
    );
}
