// /resources/js/Pages/Admin/ReservationList.jsx
import { useEffect, useState } from "react";
import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import "../../../css/pages/admin/reservation-list.css";

// ✅ JST（Asia/Tokyo）で "YYYY年MM月DD日" を返すフォーマッタ
const dateFormatterJST = new Intl.DateTimeFormat("ja-JP", {
    timeZone: "Asia/Tokyo",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
});

/**
 * ⏰ 時刻表示を「HH:mm」に揃える（管理画面は “表示の安定” を優先）
 */
function formatTimeToHHmm(value) {
    if (value == null) return "";

    // 1) value がオブジェクト（CarbonがJSON化された等）の場合
    if (typeof value === "object") {
        if (value.date) {
            return formatTimeToHHmm(value.date);
        }
        try {
            return formatTimeToHHmm(JSON.stringify(value));
        } catch {
            return "";
        }
    }

    const str = String(value).trim();
    if (!str) return "";

    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    const m = str.match(/\b(\d{2}:\d{2})(?::\d{2})?\b/);
    if (m) return m[1];

    const d = new Date(str);
    if (!isNaN(d.getTime())) {
        const hh = String(d.getHours()).padStart(2, "0");
        const mm = String(d.getMinutes()).padStart(2, "0");
        return `${hh}:${mm}`;
    }

    return "";
}

/**
 * 📅 日付表示を「YYYY年MM月DD日」に揃える
 */
function formatDateToJapanese(value) {
    if (!value) return "";

    // Carbon JSON object などの保険
    if (typeof value === "object" && value.date) {
        return formatDateToJapanese(value.date);
    }

    const str = String(value).trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, d] = str.split("-");
        return `${y}年${m}月${d}日`;
    }

    const d = new Date(str);
    if (isNaN(d.getTime())) {
        return str;
    }

    const formatted = dateFormatterJST.format(d);
    const parts = formatted.split(/[\/.-]/);
    if (parts.length >= 3) {
        const [y, m, day] = parts;
        return `${y}年${m}月${day}日`;
    }

    return formatted;
}

/**
 * ✅ "YYYY-MM-DD" をローカル日付として安全に Date 化（曜日判定用）
 */
function safeDateFromYmd(value) {
    if (!value) return null;

    if (typeof value === "object" && value.date) {
        return safeDateFromYmd(value.date);
    }

    const str = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, d] = str.split("-").map(Number);
        return new Date(y, m - 1, d);
    }
    const dt = new Date(str);
    return isNaN(dt.getTime()) ? null : dt;
}

/**
 * ✅ 予約の並び替え用キー（date → start_time の昇順）
 */
function reservationSortKey(r) {
    const rawDate = r?.date?.date ? String(r.date.date) : String(r?.date ?? "");
    const d = rawDate.trim();
    const dateKey = /^\d{4}-\d{2}-\d{2}$/.test(d) ? d : d.slice(0, 10);

    const timeKey = formatTimeToHHmm(r?.start_time) || "00:00";
    return `${dateKey} ${timeKey}`;
}

/**
 * ✅ status を日本語にする（必要に応じて追加OK）
 */
function statusToJa(status) {
    const s = String(status || "").trim().toLowerCase();
    if (!s) return "予約中";

    const map = {
        confirmed: "確定",
        pending: "仮予約",
        canceled: "キャンセル",
        cancelled: "キャンセル",
        done: "完了",
    };

    return map[s] || status; // 未知の値はそのまま表示
}

export default function ReservationList() {
    const [reservations, setReservations] = useState([]);
    const [businessHours, setBusinessHours] = useState([]);
    const [loading, setLoading] = useState(true);

    // ✅ 追加：詳細表示の開閉（1件だけ開く）
    const [openId, setOpenId] = useState(null);

    // 営業時間データの取得
    useEffect(() => {
        async function fetchBusinessHours() {
            try {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1;

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
    }, []);

    // 予約データの取得
    useEffect(() => {
        async function fetchReservations() {
            try {
                const res = await fetch("/api/admin/reservations");
                if (res.ok) {
                    const data = await res.json();

                    const sorted = Array.isArray(data)
                        ? [...data].sort((a, b) =>
                            reservationSortKey(a).localeCompare(
                                reservationSortKey(b)
                            )
                        )
                        : [];

                    setReservations(sorted);
                }
            } catch (err) {
                console.error("予約一覧の取得に失敗:", err);
            } finally {
                setLoading(false);
            }
        }
        fetchReservations();
    }, []);

    // 予約の時間表示（営業中/営業時間外のラベルも付ける）
    const getFormattedTime = (dateObj, startTimeRaw) => {
        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayIndex =
            dateObj instanceof Date && !isNaN(dateObj.getTime())
                ? dateObj.getDay()
                : 0;

        const selectedDay = dayOfWeekNames[dayIndex];

        const hourInfo = businessHours.find((h) => h.day_of_week === selectedDay);
        const startTime = formatTimeToHHmm(startTimeRaw);

        if (hourInfo && !hourInfo.is_closed) {
            return `${startTime}（営業中）`;
        }

        return `${startTime}（営業時間外）`;
    };

    const handleDelete = async (id) => {
        if (!confirm("この予約を削除しますか？")) return;
        const res = await fetch(`/api/admin/reservations/${id}`, {
            method: "DELETE",
        });
        if (res.ok) {
            setReservations((prev) => prev.filter((r) => r.id !== id));
            setOpenId((cur) => (cur === id ? null : cur));
        }
    };

    if (loading) {
        return <p className="admin-reservation-loading">読み込み中...</p>;
    }

    return (
        <div className="admin-reservation-page">
            <div className="admin-reservation-back">
                <Link
                    href={route("admin.dashboard")}
                    className="admin-reservation-back-link"
                >
                    前のページに戻る
                </Link>
            </div>

            <h1 className="admin-reservation-title">予約一覧</h1>

            <div className="admin-reservation-table-wrapper">
                <table className="admin-reservation-table">
                    <thead>
                        <tr>
                            {/* ✅ 一覧では非表示（詳細で確認） */}
                            {/* <th>ID</th> */}
                            <th>氏名</th>
                            {/* <th>メール</th> */}
                            {/* <th>電話</th> */}
                            <th>メーカー</th>
                            <th>車種</th>
                            <th>コース</th>
                            <th>メニュー</th>
                            <th>日付</th>
                            <th>時間</th>
                            <th>状態</th>
                            <th>操作</th>
                        </tr>
                    </thead>

                    <tbody>
                        {reservations.map((r) => {
                            const dateObj = safeDateFromYmd(r.date);
                            const isOpen = openId === r.id;

                            return (
                                <>
                                    <tr key={r.id} className="admin-reservation-row">
                                        {/* ✅ 一覧では非表示（詳細で確認） */}
                                        {/* <td className="admin-reservation-cell admin-reservation-cell--id">
                                            {r.id}
                                        </td> */}

                                        <td className="admin-reservation-cell">{r.name}</td>

                                        {/* ✅ 一覧では非表示（詳細で確認） */}
                                        {/* <td className="admin-reservation-cell">{r.email || "-"}</td> */}
                                        {/* <td className="admin-reservation-cell">{r.phone || "-"}</td> */}

                                        <td className="admin-reservation-cell">
                                            {r.maker || "-"}
                                        </td>
                                        <td className="admin-reservation-cell">
                                            {r.car_model || "-"}
                                        </td>
                                        <td className="admin-reservation-cell">
                                            {r.course || "-"}
                                        </td>

                                        <td className="admin-reservation-cell">
                                            {r.service_name}
                                        </td>

                                        <td className="admin-reservation-cell admin-reservation-cell--date">
                                            {formatDateToJapanese(r.date)}
                                        </td>

                                        <td className="admin-reservation-cell admin-reservation-cell--time">
                                            {getFormattedTime(dateObj, r.start_time)}
                                        </td>

                                        <td className="admin-reservation-cell">
                                            <span className="admin-reservation-status">
                                                {statusToJa(r.status)}
                                            </span>
                                        </td>

                                        <td className="admin-reservation-actions">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setOpenId((cur) =>
                                                        cur === r.id ? null : r.id
                                                    )
                                                }
                                                className="admin-reservation-button"
                                                style={{
                                                    border: "1px solid var(--border-color)",
                                                    background: "var(--color-bg-soft)",
                                                    color: "var(--salon-brown)",
                                                }}
                                            >
                                                {isOpen ? "詳細を閉じる" : "詳細"}
                                            </button>

                                            <Link
                                                href={route("admin.reservations.edit", r.id)}
                                                className="admin-reservation-button admin-reservation-button--edit"
                                            >
                                                編集
                                            </Link>

                                            <button
                                                onClick={() => handleDelete(r.id)}
                                                className="admin-reservation-button admin-reservation-button--delete"
                                            >
                                                削除
                                            </button>
                                        </td>
                                    </tr>

                                    {/* ✅ 追加：詳細（ユーザー入力を全部表示） */}
                                    {isOpen && (
                                        <tr className="admin-reservation-row">
                                            <td
                                                className="admin-reservation-cell"
                                                colSpan={9}
                                                style={{ background: "rgba(0,0,0,0.02)" }}
                                            >
                                                <div style={{ padding: "0.75rem 0.5rem" }}>
                                                    <div
                                                        style={{
                                                            fontWeight: 700,
                                                            marginBottom: "0.5rem",
                                                        }}
                                                    >
                                                        入力内容（詳細）
                                                    </div>

                                                    <div style={{ display: "grid", gap: "0.25rem" }}>
                                                        {/* ✅ 一覧非表示分を詳細に集約 */}
                                                        <div>ID：{r.id ?? "-"}</div>
                                                        <div>お名前：{r.name || "-"}</div>
                                                        <div>メール：{r.email || "-"}</div>
                                                        <div>電話：{r.phone || "-"}</div>

                                                        <div>メーカー：{r.maker || "-"}</div>
                                                        <div>車種：{r.car_model || "-"}</div>
                                                        <div>コース：{r.course || "-"}</div>
                                                        <div>メニュー：{r.service_name || "-"}</div>
                                                        <div>日付：{formatDateToJapanese(r.date)}</div>
                                                        <div>
                                                            時間：{formatTimeToHHmm(r.start_time)}
                                                            {r.end_time
                                                                ? ` 〜 ${formatTimeToHHmm(
                                                                    r.end_time
                                                                )}`
                                                                : ""}
                                                        </div>
                                                        <div>状態：{statusToJa(r.status)}</div>

                                                        <div style={{ marginTop: "0.4rem" }}>
                                                            備考：
                                                            <div
                                                                style={{
                                                                    whiteSpace: "pre-wrap",
                                                                    wordBreak: "break-word",
                                                                    background:
                                                                        "rgba(255,255,255,0.7)",
                                                                    border: "1px solid var(--border-color)",
                                                                    borderRadius: "10px",
                                                                    padding: "0.5rem",
                                                                    marginTop: "0.25rem",
                                                                }}
                                                            >
                                                                {String(r.notes || "").trim() ||
                                                                    "（なし）"}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
