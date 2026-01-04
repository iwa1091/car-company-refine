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
 * ✅ status を日本語表示へ（英語→日本語）
 * - 既に日本語ならそのまま
 * - 未知の値は原文を返す（壊さない）
 */
function formatStatusToJapanese(status) {
    if (status == null) return "予約中";

    const raw = String(status).trim();
    if (!raw) return "予約中";

    // 既に日本語が含まれている場合はそのまま
    if (/[ぁ-んァ-ン一-龥]/.test(raw)) return raw;

    const key = raw
        .toLowerCase()
        .replace(/\s+/g, "")
        .replace(/-/g, "_");

    const map = {
        pending: "予約中",
        reserved: "予約中",
        booking: "予約中",
        booked: "予約中",

        confirmed: "確定",
        approved: "確定",

        completed: "完了",
        done: "完了",

        canceled: "キャンセル",
        cancelled: "キャンセル",
        cancel: "キャンセル",

        no_show: "無断キャンセル",
        noshow: "無断キャンセル",

        in_progress: "対応中",
        processing: "対応中",

        rejected: "却下",

        paid: "支払い済み",
        unpaid: "未払い",
        refunded: "返金済み",

        expired: "期限切れ",
    };

    return map[key] ?? raw;
}

/**
 * ⏰ 時刻表示を「HH:mm」に揃える（管理画面は “表示の安定” を優先）
 *
 * 想定入力（API次第で揺れる）
 * - "09:30:00" / "09:30"
 * - "2025-12-29T09:30:00.000000Z"（ISO）
 * - { date: "2025-12-29 09:30:00.000000", timezone: "UTC", ... }（CarbonがJSON化）
 *
 * 方針：
 * - Date変換に頼らず、とにかく「文字列からHH:mmを抜く」
 * - それでも取れない時だけ保険で Date を試す（最終手段）
 */
function formatTimeToHHmm(value) {
    if (value == null) return "";

    // 1) value がオブジェクト（CarbonがJSON化された等）の場合
    if (typeof value === "object") {
        // よくある形: { date: "...", timezone: "...", ... }
        if (value.date) {
            return formatTimeToHHmm(value.date);
        }
        // 想定外は JSON 文字列化して拾えるか試す
        try {
            return formatTimeToHHmm(JSON.stringify(value));
        } catch {
            return "";
        }
    }

    const str = String(value).trim();
    if (!str) return "";

    // 2) "HH:MM" / "HH:MM:SS" はそのまま
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    // 3) どんな文字列でも "HH:MM" を抜く（ISOでもスペース区切りでもOK）
    const m = str.match(/\b(\d{2}:\d{2})(?::\d{2})?\b/);
    if (m) return m[1];

    // 4) それでもダメなら Date を試す（最終手段：ズレる可能性があるので保険扱い）
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
 * - "YYYY-MM-DD" は new Date() でズレやすいので手組み
 */
function formatDateToJapanese(value) {
    if (!value) return "";

    const str = String(value).trim();

    // "YYYY-MM-DD" は安全に手組み
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, d] = str.split("-");
        return `${y}年${m}月${d}日`;
    }

    // それ以外は DateTimeFormat（JST）
    const d = new Date(str);
    if (isNaN(d.getTime())) {
        return str;
    }

    const formatted = dateFormatterJST.format(d); // 例: "2025/12/29"
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
 * - Date変換に頼らず、「YYYY-MM-DD HH:mm」形式の文字列に寄せて比較する
 */
function reservationSortKey(r) {
    // date は "YYYY-MM-DD" 想定。ISOなどでも先頭10文字を拾う
    const rawDate = r?.date?.date ? String(r.date.date) : String(r?.date ?? "");
    const d = rawDate.trim();
    const dateKey = /^\d{4}-\d{2}-\d{2}$/.test(d) ? d : d.slice(0, 10);

    // start_time は既存関数で "HH:mm" に寄せる
    const timeKey = formatTimeToHHmm(r?.start_time) || "00:00";

    return `${dateKey} ${timeKey}`;
}

export default function ReservationList() {
    const [reservations, setReservations] = useState([]);
    const [businessHours, setBusinessHours] = useState([]);
    const [loading, setLoading] = useState(true);

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

                    // ✅ 予約の日時が早い順（date → start_time）に並び替え
                    const sorted = Array.isArray(data)
                        ? [...data].sort((a, b) =>
                            reservationSortKey(a).localeCompare(
                                reservationSortKey(b)
                            )
                        )
                        : [];

                    setReservations(sorted);

                    // ✅ デバッグしたい時は一時的に有効化
                    // console.log("API sample:", data?.[0]?.date, data?.[0]?.start_time);
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

        // ※週は考慮せず曜日ベース（既存仕様）
        const hourInfo = businessHours.find((h) => h.day_of_week === selectedDay);

        // ✅ 表示は文字列抽出で固定（ズレ防止）
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
                            <th>ID</th>
                            <th>氏名</th>
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

                            return (
                                <tr key={r.id} className="admin-reservation-row">
                                    <td className="admin-reservation-cell admin-reservation-cell--id">
                                        {r.id}
                                    </td>
                                    <td className="admin-reservation-cell">{r.name}</td>
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
                                            {formatStatusToJapanese(r.status)}
                                        </span>
                                    </td>
                                    <td className="admin-reservation-actions">
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
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
