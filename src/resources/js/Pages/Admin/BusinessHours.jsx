// /resources/js/Pages/Admin/BusinessHours.jsx
import { useState, useEffect } from "react";
import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/business-hours.css";

export default function BusinessHours() {
    const [hours, setHours] = useState([]);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState("");
    const [selectedMonth, setSelectedMonth] = useState(
        new Date().getMonth() + 1
    ); // 今月
    const [selectedYear, setSelectedYear] = useState(
        new Date().getFullYear()
    );
    const [selectedWeek, setSelectedWeek] = useState(1);

    // 営業時間を取得
    const fetchWeeklyHours = async (year, month) => {
        setLoading(true);
        try {
            const res = await fetch(
                `/api/business-hours/weekly?year=${year}&month=${month}`
            );
            const data = await res.json();
            setHours(data);
        } catch (err) {
            console.error("営業時間取得失敗:", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchWeeklyHours(selectedYear, selectedMonth);
    }, [selectedYear, selectedMonth]);

    // 値の変更ハンドラ
    const handleChange = (index, field, value) => {
        const updated = [...hours];
        updated[index][field] = value;

        // 休業日チェック時は時間をクリア
        if (field === "is_closed" && value === true) {
            updated[index].open_time = null;
            updated[index].close_time = null;
        }

        setHours(updated);
    };

    // 保存処理
    const handleSave = async () => {
        try {
            const res = await fetch("/api/business-hours/weekly", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(hours),
            });

            if (res.ok) {
                setMessage("営業時間を更新しました。");
                setTimeout(() => setMessage(""), 3000);
            } else {
                setMessage("更新に失敗しました。");
            }
        } catch (err) {
            console.error("更新エラー:", err);
            setMessage("サーバー通信エラーが発生しました。");
        }
    };

    // 表示する週データをフィルタ
    const filteredHours = hours.filter(
        (h) => h.week_of_month === selectedWeek
    );

    // 月のプルダウン（今月・来月）
    const months = [
        { label: "今月", value: new Date().getMonth() + 1 },
        {
            label: "来月",
            value:
                new Date().getMonth() + 2 > 12
                    ? 1
                    : new Date().getMonth() + 2,
        },
    ];

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
                {/* 🔙 ダッシュボードへ戻る */}
                <div className="business-hours-back-area">
                    <Link
                        href={route("admin.dashboard")}
                        className="business-hours-back-button"
                    >
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="business-hours-title">
                    営業日・営業時間設定（週単位・15分刻み）
                </h1>

                {message && (
                    <p className="business-hours-message">{message}</p>
                )}

                {/* 月・週セレクト */}
                <div className="business-hours-controls">
                    {/* 年・月セレクト */}
                    <select
                        value={selectedMonth}
                        onChange={(e) =>
                            setSelectedMonth(Number(e.target.value))
                        }
                        className="business-hours-month-select"
                    >
                        {months.map((m) => (
                            <option key={m.value} value={m.value}>
                                {selectedYear}年 {m.value}月（{m.label}）
                            </option>
                        ))}
                    </select>

                    {/* 週タブ */}
                    <div className="business-hours-week-tabs">
                        {[1, 2, 3, 4, 5].map((week) => (
                            <button
                                key={week}
                                onClick={() => setSelectedWeek(week)}
                                className={
                                    "business-hours-week-button" +
                                    (selectedWeek === week
                                        ? " business-hours-week-button--active"
                                        : "")
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
                            {filteredHours.map((h) => (
                                <tr
                                    key={`${h.day_of_week}-${h.week_of_month}`}
                                >
                                    <td className="business-hours-day-cell">
                                        {h.day_of_week}
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            step="900" // 15分単位
                                            value={h.open_time || ""}
                                            onChange={(e) =>
                                                handleChange(
                                                    hours.indexOf(h),
                                                    "open_time",
                                                    e.target.value
                                                )
                                            }
                                            disabled={h.is_closed}
                                            className="business-hours-time-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            step="900" // 15分単位
                                            value={h.close_time || ""}
                                            onChange={(e) =>
                                                handleChange(
                                                    hours.indexOf(h),
                                                    "close_time",
                                                    e.target.value
                                                )
                                            }
                                            disabled={h.is_closed}
                                            className="business-hours-time-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="checkbox"
                                            checked={h.is_closed}
                                            onChange={(e) =>
                                                handleChange(
                                                    hours.indexOf(h),
                                                    "is_closed",
                                                    e.target.checked
                                                )
                                            }
                                            className="business-hours-closed-checkbox"
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* 保存ボタン */}
                <div className="business-hours-save-area">
                    <button
                        onClick={handleSave}
                        className="business-hours-save-button"
                    >
                        保存する
                    </button>
                </div>
            </div>
        </div>
    );
}
