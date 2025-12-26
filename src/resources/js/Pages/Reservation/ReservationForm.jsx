// /resources/js/Pages/Reservation/ReservationForm.jsx
import { useState, useEffect } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/reservation/reservation-form.css";

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

export default function ReservationForm() {
    const [date, setDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState("");
    const [formData, setFormData] = useState({
        name: "",
        phone: "",
        service_id: "",
        email: "",
        notes: "",
    });
    const [services, setServices] = useState([]);
    const [businessHours, setBusinessHours] = useState([]); // 営業時間データ
    const [availableTimes, setAvailableTimes] = useState([]); // 予約可能時間
    const [message, setMessage] = useState("");

    // サービス一覧の取得
    useEffect(() => {
        async function fetchServices() {
            try {
                const res = await fetch("/api/services");
                if (res.ok) {
                    const data = await res.json();
                    setServices(data);
                }
            } catch (err) {
                console.error("サービス一覧の取得に失敗:", err);
            }
        }
        fetchServices();
    }, []);

    // 営業時間の取得（来月のデータも取得できるように修正）
    useEffect(() => {
        async function fetchBusinessHours() {
            try {
                const year = date.getFullYear();
                const month = date.getMonth() + 1; // 月の更新に対応

                const res = await fetch(
                    `/api/business-hours/weekly?year=${year}&month=${month}`
                );
                if (res.ok) {
                    const data = await res.json();
                    setBusinessHours(data);
                }
            } catch (err) {
                console.error("営業時間の取得に失敗:", err);
            }
        }
        fetchBusinessHours();
    }, [date]); // `date`が変わる度に再取得

    // 選択された日付に応じて予約可能時間を更新
    useEffect(() => {
        if (businessHours.length === 0) return;

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];

        // 週ごとにデータをフィルタリング
        const weekOfMonth = Math.ceil(date.getDate() / 7); // 現在の日付から週番号を取得
        const weeklyHours = businessHours.filter(
            (h) => h.week_of_month === weekOfMonth && h.day_of_week === selectedDay
        );

        // 営業時間が存在する場合に時間スロットを生成
        if (weeklyHours.length > 0) {
            const hourInfo = weeklyHours[0]; // 1週間分のデータがある場合、最初の1つを使用
            if (hourInfo.is_closed) {
                setAvailableTimes([]); // 営業時間外
            } else {
                const slots = generateTimeSlots(
                    hourInfo.open_time,
                    hourInfo.close_time,
                    15
                ); // 15分単位
                setAvailableTimes(slots);
            }
        } else {
            setAvailableTimes([]); // 営業時間外
        }
    }, [date, businessHours]); // businessHoursが更新されるたびに再実行

    // カレンダーの無効化（日曜など休業日）
    const tileDisabled = ({ date }) => {
        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];

        const dayInfo = businessHours.find((h) => h.day_of_week === selectedDay);

        return !dayInfo || dayInfo.is_closed;
    };

    // 入力変更ハンドラ
    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    // 送信処理
    const handleSubmit = async (e) => {
        e.preventDefault();
        setMessage("");

        if (!selectedTime) {
            setMessage("時間を選択してください。");
            return;
        }

        const payload = {
            ...formData,
            date: date.toISOString().split("T")[0],
            start_time: selectedTime,
        };

        try {
            const response = await fetch("/api/reservations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const data = await response.json();
                setMessage("✅ ご予約が完了しました！メールをご確認ください。");
                console.log("予約成功:", data);

                // 入力リセット
                setSelectedTime("");
                setFormData({
                    name: "",
                    phone: "",
                    service_id: "",
                    email: "",
                    notes: "",
                });
            } else {
                const errorData = await response.json();
                setMessage(errorData.message || "⚠️ 予約に失敗しました。");
            }
        } catch (err) {
            console.error("送信エラー:", err);
            setMessage("⚠️ サーバー通信エラーが発生しました。");
        }
    };

    // 🔙 メニュー・料金ページ（menu_price.blade.php）へ戻る
    const handleBack = () => {
        // Blade 側のルート `/menu_price` へ遷移
        window.location.href = "/menu_price";
    };

    return (
        <main className="reservation-main">
            {/* 前のページに戻るボタン */}
            <div className="reservation-back">
                <button
                    type="button"
                    onClick={handleBack}
                    className="reservation-back-button"
                >
                    前のページに戻る
                </button>
            </div>

            <h1 className="reservation-title">ご予約フォーム</h1>

            {message && (
                <p
                    className={`reservation-message ${message.includes("✅")
                            ? "reservation-message--success"
                            : "reservation-message--error"
                        }`}
                >
                    {message}
                </p>
            )}

            <form
                onSubmit={handleSubmit}
                className="reservation-form-card"
            >
                {/* 名前 */}
                <div className="reservation-field">
                    <label className="reservation-label">お名前</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* メール */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メールアドレス
                    </label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* 電話番号 */}
                <div className="reservation-field">
                    <label className="reservation-label">電話番号</label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* メニュー選択 */}
                <div className="reservation-field">
                    <label className="reservation-label">メニュー</label>
                    <select
                        name="service_id"
                        value={formData.service_id}
                        onChange={handleChange}
                        required
                        className="reservation-select"
                    >
                        <option value="">選択してください</option>
                        {services.map((service) => (
                            <option key={service.id} value={service.id}>
                                {service.name}（¥{service.price} /{" "}
                                {service.duration_minutes}
                                分）
                            </option>
                        ))}
                    </select>
                </div>

                {/* カレンダー */}
                <div className="reservation-field">
                    <label className="reservation-label">ご希望日</label>
                    <div className="reservation-calendar-wrapper">
                        <div className="reservation-calendar">
                            <Calendar
                                onChange={setDate}
                                value={date}
                                tileDisabled={tileDisabled}
                            />
                        </div>
                        <p className="reservation-date-text">
                            選択された日付: {date.toLocaleDateString()}
                        </p>
                    </div>
                </div>

                {/* 時間枠選択 */}
                <div className="reservation-field">
                    <label className="reservation-label">ご希望時間</label>

                    <div className="reservation-time-wrapper">
                        {availableTimes.length === 0 ? (
                            <p className="reservation-time-note">
                                ※ この日は休業日または営業時間外です
                            </p>
                        ) : (
                            <div className="reservation-time-grid">
                                {availableTimes.map((time) => (
                                    <button
                                        type="button"
                                        key={time}
                                        onClick={() => setSelectedTime(time)}
                                        className={`reservation-time-button ${selectedTime === time
                                                ? "reservation-time-button--selected"
                                                : ""
                                            }`}
                                    >
                                        {time}
                                    </button>
                                ))}
                            </div>
                        )}

                        {selectedTime && (
                            <p className="reservation-selected-time">
                                選択された時間: {selectedTime}
                            </p>
                        )}
                    </div>
                </div>

                {/* 備考 */}
                <div className="reservation-field">
                    <label className="reservation-label">備考</label>
                    <textarea
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                        rows={3}
                        className="reservation-textarea"
                    />
                </div>

                {/* 送信ボタン */}
                <button
                    type="submit"
                    className="reservation-submit-button"
                >
                    予約する
                </button>
            </form>
        </main>
    );
}
