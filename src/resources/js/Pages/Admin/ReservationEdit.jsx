// /resources/js/Pages/Admin/ReservationEdit.jsx
import { useState, useEffect } from "react";
import { router, usePage, Link } from "@inertiajs/react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/admin/reservation-edit.css";

/**
 * 15分刻みで時間スロットを生成
 */
function generateTimeSlots(start, end, interval = 15) {
    const slots = [];
    if (!start || !end) return slots;

    let [hour, minute] = start.split(":").map(Number);
    const [endHour, endMinute] = end.split(":").map(Number);

    while (hour < endHour || (hour === endHour && minute <= endMinute)) {
        const time = `${String(hour).padStart(2, "0")}:${String(
            minute
        ).padStart(2, "0")}`;
        slots.push(time);
        minute += interval;
        if (minute >= 60) {
            hour += 1;
            minute -= 60;
        }
    }
    return slots;
}

// ⏰ 時刻表示を「HH:mm」に揃えるヘルパー
function formatTimeToHHmm(value) {
    if (!value) return "00:00";

    // "HH:MM" or "HH:MM:SS" 形式なら先頭5文字を使用
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        return value.slice(0, 5);
    }

    // それ以外は Date としてパースを試みる（保険）
    const d = new Date(value);
    if (isNaN(d.getTime())) {
        return "00:00";
    }
    const h = String(d.getHours()).padStart(2, "0");
    const m = String(d.getMinutes()).padStart(2, "0");
    return `${h}:${m}`;
}

// 📅「0000年00月00日00:00」形式に整形するヘルパー
function formatDateTimeJp(dateStr, timeStr) {
    if (!dateStr) return "";

    // "YYYY-MM-DD" を前提にパース
    const parts = dateStr.split("-");
    if (parts.length !== 3) return dateStr;

    const [y, m, d] = parts;
    const year = y;
    const month = String(m).padStart(2, "0");
    const day = String(d).padStart(2, "0");

    const time = formatTimeToHHmm(timeStr);

    return `${year}年${month}月${day}日${time}`;
}

export default function ReservationEdit() {
    const { reservation } = usePage().props;
    const [formData, setFormData] = useState({
        name: reservation.name,
        date: reservation.date,
        start_time: reservation.start_time,
        service_id: reservation.service_id, // サービスIDも保持
        service_duration: reservation.service?.duration_minutes || 0, // 所要時間を保持
    });

    const [businessHours, setBusinessHours] = useState([]);
    const [availableTimes, setAvailableTimes] = useState([]);

    // 営業時間データ取得
    useEffect(() => {
        async function fetchBusinessHours() {
            const res = await fetch("/api/business-hours");
            if (res.ok) {
                const data = await res.json();
                setBusinessHours(data);
            }
        }
        fetchBusinessHours();
    }, []);

    // 営業日判定関数
    const tileDisabled = ({ date }) => {
        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const target = businessHours.find(
            (b) => b.day_of_week === dayNames[date.getDay()]
        );
        return !target || target.is_closed;
    };

    // 営業時間に基づいた時間スロット生成
    useEffect(() => {
        if (
            !formData.date ||
            businessHours.length === 0 ||
            !formData.service_duration
        )
            return;

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDate = new Date(formData.date);
        const target = businessHours.find(
            (b) => b.day_of_week === dayNames[selectedDate.getDay()]
        );

        if (!target || target.is_closed) {
            setAvailableTimes([]);
            return;
        }

        const slots = [];
        let [h, m] = target.open_time.split(":").map(Number);
        const [endH, endM] = target.close_time.split(":").map(Number);

        // 15分刻みで時間スロットを生成
        while (h < endH || (h === endH && m + formData.service_duration <= endM)) {
            slots.push(
                `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`
            );
            m += 15; // 15分単位

            if (m >= 60) {
                h++;
                m -= 60;
            }
        }

        setAvailableTimes(slots);
    }, [formData.date, businessHours, formData.service_duration]);

    // 入力変更
    const handleChange = (e) => {
        setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    };

    // カレンダー変更（ローカル日時から "YYYY-MM-DD" を組み立て）
    const handleDateChange = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0"); // 0始まりなので+1
        const day = String(date.getDate()).padStart(2, "0");

        setFormData((prev) => ({
            ...prev,
            date: `${year}-${month}-${day}`,
        }));
    };

    // 更新処理
    const handleSubmit = (e) => {
        e.preventDefault();
        router.put(route("admin.reservations.update", reservation.id), formData);
    };

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
                                    value={new Date(formData.date)}
                                    onChange={handleDateChange}
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
                                    {availableTimes.map((time) => (
                                        <button
                                            key={time}
                                            type="button"
                                            onClick={() =>
                                                setFormData((prev) => ({
                                                    ...prev,
                                                    start_time: time,
                                                }))
                                            }
                                            className={`admin-reservation-edit-time-button ${formData.start_time === time
                                                    ? "admin-reservation-edit-time-button--selected"
                                                    : ""
                                                }`}
                                        >
                                            {time}
                                        </button>
                                    ))}
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
                    >
                        更新
                    </button>
                </form>
            </div>
        </div>
    );
}
