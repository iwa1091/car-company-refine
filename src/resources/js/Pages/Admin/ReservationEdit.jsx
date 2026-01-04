// /resources/js/Pages/Admin/ReservationEdit.jsx
import { useState, useEffect, useMemo } from "react";
import { router, usePage, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/admin/reservation-edit.css";

/**
 * ✅ "HH:mm" に正規化（Date変換は一切しない）
 * - "09:00" / "09:00:00" → "09:00"
 * - "2025-12-29T09:00:00.000000Z" など → "09:00"（時刻部分だけ抜く）
 * - 取れなければ "" を返す（00:00固定は誤解を生むのでやらない）
 */
function normalizeHHmm(value) {
    if (!value) return "";

    const str = String(value).trim();

    // "HH:MM" / "HH:MM:SS"
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    // ISO / datetime文字列から "HH:MM" を抽出
    const m = str.match(/\b(\d{2}:\d{2})(?::\d{2})?\b/);
    if (m) return m[1];

    return "";
}

/**
 * ✅ 日付を "YYYY-MM-DD" に正規化
 * - "YYYY-MM-DD" → そのまま
 * - ISOなど → 先頭10文字を採用
 */
function normalizeYmd(value) {
    if (!value) return "";
    const str = String(value).trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;

    // "2025-12-29T..." 形式など
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) return str.slice(0, 10);

    return "";
}

/**
 * ✅ "YYYY-MM-DD" をローカル日付として安全に Date 化（曜日/カレンダー用）
 */
function safeDateFromYmd(value) {
    const ymd = normalizeYmd(value);
    if (!ymd) return null;

    const [y, m, d] = ymd.split("-").map(Number);
    return new Date(y, m - 1, d);
}

/**
 * ✅ week_of_month をJSで計算（PHP側 BusinessHour::getWeekOfMonth と合わせる）
 * PHP:
 *   ceil((day + firstDay->dayOfWeekIso - 1)/7)
 * JS:
 *   dayOfWeekIso: Mon=1..Sun=7（JSは Sun=0..Sat=6）
 */
function getWeekOfMonth(dateObj) {
    if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) return 1;

    const day = dateObj.getDate();

    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay(); // Sun(0)→7

    return Math.ceil((day + firstIso - 1) / 7);
}

/**
 * 📅「YYYY年MM月DD日 HH:mm」形式に整形（表示用）
 */
function formatDateTimeJp(ymd, timeHHmm) {
    const dateStr = normalizeYmd(ymd);
    if (!dateStr) return "";

    const [y, m, d] = dateStr.split("-");
    const time = normalizeHHmm(timeHHmm) || "--:--";

    return `${y}年${m}月${d}日 ${time}`;
}

/**
 * ✅ "HH:mm" を分→ "HH:mm" に戻す
 */
function minutesToHHmm(totalMinutes) {
    const h = String(Math.floor(totalMinutes / 60)).padStart(2, "0");
    const m = String(totalMinutes % 60).padStart(2, "0");
    return `${h}:${m}`;
}

/**
 * ✅ "HH:mm" → 分
 */
function hhmmToMinutes(hhmm) {
    const t = normalizeHHmm(hhmm);
    if (!t) return null;
    const [h, m] = t.split(":").map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    return h * 60 + m;
}

export default function ReservationEdit() {
    const { reservation } = usePage().props;

    // 初期値を “必ず” 正規化（ここがないとボタン選択の一致が崩れます）
    const initialDate = normalizeYmd(reservation?.date);
    const initialStart = normalizeHHmm(reservation?.start_time);
    const initialDuration = Number(reservation?.service?.duration_minutes || 0);

    // 表示中の月（カレンダーが見ている月）の営業時間を取るための state
    const initialDateObj = safeDateFromYmd(initialDate) || new Date();
    const [activeYear, setActiveYear] = useState(initialDateObj.getFullYear());
    const [activeMonth, setActiveMonth] = useState(initialDateObj.getMonth() + 1);

    const [formData, setFormData] = useState(() => {
        // end_time を持っていれば正規化、無ければ duration から計算（保険）
        const rawEnd = reservation?.end_time;
        let end = normalizeHHmm(rawEnd);

        if (!end && initialStart && initialDuration > 0) {
            const startMin = hhmmToMinutes(initialStart);
            if (startMin !== null) {
                end = minutesToHHmm(startMin + initialDuration);
            }
        }

        return {
            name: reservation?.name || "",
            date: initialDate,
            start_time: initialStart,
            end_time: end,
            service_id: reservation?.service_id ?? null,
            service_duration: initialDuration, // 所要時間
        };
    });

    const [businessHours, setBusinessHours] = useState([]);
    const [availableTimes, setAvailableTimes] = useState([]);

    /**
     * ✅ 週単位の営業時間を取得（/api/business-hours/weekly を利用）
     * BusinessHours.jsx と合わせる
     */
    useEffect(() => {
        async function fetchBusinessHoursWeekly() {
            try {
                const res = await fetch(
                    `/api/business-hours/weekly?year=${activeYear}&month=${activeMonth}`
                );
                if (res.ok) {
                    const data = await res.json();
                    setBusinessHours(Array.isArray(data) ? data : []);
                } else {
                    setBusinessHours([]);
                }
            } catch (e) {
                console.error("営業時間取得失敗:", e);
                setBusinessHours([]);
            }
        }
        fetchBusinessHoursWeekly();
    }, [activeYear, activeMonth]);

    /**
     * ✅ カレンダーの休業日グレーアウト（week_of_month を考慮）
     */
    const tileDisabled = ({ date }) => {
        // 表示中の月以外（前月/翌月のはみ出し日）は無効にしておく（安全）
        const tileYear = date.getFullYear();
        const tileMonth = date.getMonth() + 1;
        if (tileYear !== activeYear || tileMonth !== activeMonth) {
            return true;
        }

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayOfWeek = dayNames[date.getDay()];
        const weekOfMonth = getWeekOfMonth(date);

        const target = businessHours.find(
            (b) =>
                Number(b.week_of_month) === Number(weekOfMonth) &&
                b.day_of_week === dayOfWeek
        );

        return !target || !!target.is_closed;
    };

    /**
     * ✅ 選択日（date）に対して、営業日＆営業時間に基づいた時間スロット生成
     * - week_of_month を考慮
     * - open/close は "HH:mm" に正規化
     * - duration を考慮して「開始できる最大時刻」まで生成
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

        // 選択日が別月に変わったら、その月の営業時間を取り直す
        const y = dateObj.getFullYear();
        const m = dateObj.getMonth() + 1;
        if (y !== activeYear || m !== activeMonth) {
            setActiveYear(y);
            setActiveMonth(m);
            // businessHoursが更新されてから再計算されるのでここでは抜ける
            return;
        }

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayOfWeek = dayNames[dateObj.getDay()];
        const weekOfMonth = getWeekOfMonth(dateObj);

        const target = businessHours.find(
            (b) =>
                Number(b.week_of_month) === Number(weekOfMonth) &&
                b.day_of_week === dayOfWeek
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

        const slots = [];
        for (let t = openMin; t <= lastStart; t += 15) {
            slots.push(minutesToHHmm(t));
        }

        setAvailableTimes(slots);

        // いま選ばれている start_time がスロット外ならクリア
        const current = normalizeHHmm(formData.start_time);
        if (current && !slots.includes(current)) {
            setFormData((prev) => ({ ...prev, start_time: "", end_time: "" }));
        }
    }, [
        formData.date,
        formData.service_duration,
        businessHours,
        activeYear,
        activeMonth,
    ]);

    // 入力変更（name）
    const handleChange = (e) => {
        setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    };

    // カレンダー変更（"YYYY-MM-DD" を組み立て）
    const handleDateChange = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        // 月も更新（該当月の営業時間を取得）
        setActiveYear(year);
        setActiveMonth(Number(month));

        setFormData((prev) => ({
            ...prev,
            date: `${year}-${month}-${day}`,
            // 日付を変えたら時間は一旦リセット（ズレ/不整合防止）
            start_time: "",
            end_time: "",
        }));
    };

    // カレンダーの月移動時（表示月の営業時間を取得）
    const handleActiveStartDateChange = ({ activeStartDate }) => {
        if (!activeStartDate) return;
        setActiveYear(activeStartDate.getFullYear());
        setActiveMonth(activeStartDate.getMonth() + 1);
    };

    // 時間ボタン選択
    const handlePickTime = (timeHHmm) => {
        const t = normalizeHHmm(timeHHmm);
        const duration = Number(formData.service_duration || 0);

        let end = "";
        const startMin = hhmmToMinutes(t);
        if (startMin !== null && duration > 0) {
            end = minutesToHHmm(startMin + duration);
        }

        setFormData((prev) => ({
            ...prev,
            start_time: t,
            end_time: end,
        }));
    };

    // 更新処理
    const handleSubmit = (e) => {
        e.preventDefault();

        // ✅送信前に正規化（保険）
        const payload = {
            ...formData,
            date: normalizeYmd(formData.date),
            start_time: normalizeHHmm(formData.start_time),
            end_time: normalizeHHmm(formData.end_time),
        };

        router.put(route("admin.reservations.update", reservation.id), payload);
    };

    const calendarValue = useMemo(() => {
        return safeDateFromYmd(formData.date) || new Date();
    }, [formData.date]);

    return (
        <div className="admin-reservation-edit-page">
            {/* 🔙 予約一覧へ戻るボタン */}
            <div className="admin-reservation-edit-back">
                <Link
                    href={route("admin.reservations.index")}
                    className="admin-reservation-edit-back-link"
                >
                    前のページに戻る
                </Link>
            </div>

            <div className="admin-reservation-edit-card">
                <h1 className="admin-reservation-edit-title">予約編集</h1>

                <form
                    onSubmit={handleSubmit}
                    className="admin-reservation-edit-form"
                >
                    {/* 氏名 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">
                            氏名
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            className="admin-reservation-edit-input"
                        />
                    </div>

                    {/* カレンダー */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">
                            日付
                        </label>
                        <div className="admin-reservation-edit-calendar-wrapper">
                            <div className="admin-reservation-edit-calendar">
                                <Calendar
                                    value={calendarValue}
                                    onChange={handleDateChange}
                                    onActiveStartDateChange={
                                        handleActiveStartDateChange
                                    }
                                    tileDisabled={tileDisabled}
                                />
                            </div>
                            <p className="admin-reservation-edit-date-text">
                                選択日:{" "}
                                {formatDateTimeJp(
                                    formData.date,
                                    formData.start_time
                                )}
                            </p>
                        </div>
                    </div>

                    {/* 営業時間に基づく選択可能時間 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">
                            時間
                        </label>
                        <div className="admin-reservation-edit-time-wrapper">
                            {availableTimes.length > 0 ? (
                                <div className="admin-reservation-edit-time-grid">
                                    {availableTimes.map((time) => {
                                        // ✅ ここで “選択中” を判定してクラスを付与（色を保持）
                                        const normalized = normalizeHHmm(
                                            formData.start_time
                                        );
                                        const isSelected = normalized === time;

                                        return (
                                            <button
                                                key={time}
                                                type="button"
                                                onClick={() =>
                                                    handlePickTime(time)
                                                }
                                                className={`admin-reservation-edit-time-button ${isSelected
                                                        ? "admin-reservation-edit-time-button--selected"
                                                        : ""
                                                    }`}
                                            >
                                                {time}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="admin-reservation-edit-time-empty">
                                    営業時間外または休業日です
                                </p>
                            )}
                        </div>
                    </div>

                    {/* 更新ボタン */}
                    <button
                        type="submit"
                        className="admin-reservation-edit-submit"
                        disabled={!formData.date || !formData.start_time}
                        title={
                            !formData.start_time
                                ? "時間を選択してください"
                                : ""
                        }
                    >
                        更新
                    </button>
                </form>
            </div>
        </div>
    );
}
