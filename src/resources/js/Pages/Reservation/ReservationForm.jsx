// /resources/js/Pages/Reservation/ReservationForm.jsx
import { useState, useEffect, useMemo, useRef } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/reservation/reservation-form.css";

/**
 * "HH:MM" or "HH:MM:SS" -> "HH:MM"
 */
function toHHmm(value) {
    if (!value) return "";
    const str = String(value);

    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    const d = new Date(str);
    if (isNaN(d.getTime())) return "";
    const h = String(d.getHours()).padStart(2, "0");
    const m = String(d.getMinutes()).padStart(2, "0");
    return `${h}:${m}`;
}

/**
 * ローカル日付から "YYYY-MM-DD"
 */
function toYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function isPastDay(d) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const target = new Date(d);
    target.setHours(0, 0, 0, 0);

    return target < today;
}

/**
 * service から category_id をできるだけ安全に取得
 * - /api/services が {category_id} を返していればOK
 * - 返していなければカテゴリ絞り込みは自動的に無効化されます（壊れません）
 */
function getCategoryId(service) {
    if (!service) return null;

    if (service.category_id != null) return String(service.category_id);

    // APIが category オブジェクトを返す場合に備える
    if (service.category && service.category.id != null) return String(service.category.id);

    return null;
}

function getCategoryName(service) {
    if (!service) return "";

    if (typeof service.category_name === "string" && service.category_name) return service.category_name;

    if (service.category && typeof service.category.name === "string" && service.category.name) return service.category.name;

    return "";
}

/**
 * ✅ 追加：errors オブジェクトから「最初のエラー」を取り出す
 * Laravelの 422 { errors: {field: [msg,...] } } を想定
 */
function firstError(errors) {
    if (!errors || typeof errors !== "object") return "";

    // 表示優先順（フォームの上から）
    const order = [
        "name",
        "email",
        "maker",
        "car_model",
        "phone",
        "service_id",
        "date",
        "start_time",
        "end_time",
        "notes",
    ];

    for (const key of order) {
        const v = errors[key];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
    }

    // 念のため、順序に無いキーも拾う
    for (const k of Object.keys(errors)) {
        const v = errors[k];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
    }

    return "";
}

export default function ReservationForm() {
    const [date, setDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState("");

    const [formData, setFormData] = useState({
        name: "",
        phone: "",
        service_id: "",
        email: "",
        maker: "",
        car_model: "",
        // ✅ 追加（コース）
        course: "",
        notes: "",
    });

    const [services, setServices] = useState([]);

    const [availabilityLoading, setAvailabilityLoading] = useState(false);
    const [availabilityError, setAvailabilityError] = useState(false);

    // ✅ APIから返ってきた「空き枠（start/end）」を保持
    const [availableSlotsByApi, setAvailableSlotsByApi] = useState([]);

    // ✅ API message（休業/不正/空きなし など）
    const [availabilityMessage, setAvailabilityMessage] = useState("");

    const [message, setMessage] = useState("");

    // ✅ 追加：バリデーション errors（サーバー422/ローカルチェック共通）
    const [errors, setErrors] = useState({});

    // ✅ カレンダー休業日（business_hours 由来）
    const [closedDates, setClosedDates] = useState(new Set());
    const [loadedYm, setLoadedYm] = useState({ year: null, month: null });

    // ✅ 追加：カテゴリフィルタ（menu_price → reservation で初期カテゴリを引き継ぐ）
    const [selectedCategoryId, setSelectedCategoryId] = useState("");
    const didInitFromQuery = useRef(false);

    const selectedYmd = useMemo(() => toYmd(date), [date]);
    const isServiceSelected = !!formData.service_id;

    const displayTimes = useMemo(() => {
        if (!isServiceSelected) return [];
        if (!Array.isArray(availableSlotsByApi) || availableSlotsByApi.length === 0) return [];
        return availableSlotsByApi
            .map((s) => toHHmm(s?.start))
            .filter(Boolean);
    }, [isServiceSelected, availableSlotsByApi]);

    // ✅ 追加：services からカテゴリ一覧を生成（category_id がある場合のみ有効）
    const categories = useMemo(() => {
        const map = new Map();
        if (!Array.isArray(services)) return [];

        services.forEach((s) => {
            const cid = getCategoryId(s);
            if (!cid) return;

            const name = getCategoryName(s);
            if (!map.has(cid)) {
                map.set(cid, name || `カテゴリ${cid}`);
            }
        });

        return Array.from(map, ([id, name]) => ({ id, name }));
    }, [services]);

    const canFilterByCategory = categories.length > 0;

    // ✅ 追加：カテゴリでサービスを絞り込み
    const filteredServices = useMemo(() => {
        if (!Array.isArray(services)) return [];
        if (!canFilterByCategory) return services;
        if (!selectedCategoryId) return services;
        return services.filter((s) => getCategoryId(s) === String(selectedCategoryId));
    }, [services, selectedCategoryId, canFilterByCategory]);

    // ✅ 追加：フォーム先頭に出すメッセージ（A案：フォーム外は使わない）
    const topMessage = useMemo(() => {
        const fe = firstError(errors);
        if (fe) return fe;
        return message || "";
    }, [errors, message]);

    const topMessageIsSuccess = useMemo(() => {
        // errors がある時は必ずエラー扱い
        if (firstError(errors)) return false;
        return typeof message === "string" && message.includes("✅");
    }, [errors, message]);

    /**
     * ✅ 最小追加：フィールド別エラーを1件だけ取り出す
     * - errors[field] が配列なら先頭
     * - 文字列ならそのまま
     */
    const fieldError = (field) => {
        if (!errors || typeof errors !== "object") return "";
        const v = errors[field];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
        return "";
    };

    // -------------------------
    // サービス一覧取得
    // -------------------------
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

    // ✅ 追加：URLクエリ（service_id / category_id）を初期反映
    useEffect(() => {
        if (didInitFromQuery.current) return;
        if (!Array.isArray(services) || services.length === 0) return;

        const params = new URLSearchParams(window.location.search);
        const qsServiceId = params.get("service_id");    // menu_price から
        const qsCategoryId = params.get("category_id");  // menu_price から（推奨）

        let nextServiceId = qsServiceId ? String(qsServiceId) : "";
        let nextCategoryId = qsCategoryId ? String(qsCategoryId) : "";

        const picked = nextServiceId
            ? services.find((s) => String(s.id) === String(nextServiceId))
            : null;

        if (!picked) {
            nextServiceId = "";
        }

        // category_id が無ければ service から補完（service が category_id を持つ前提）
        if (!nextCategoryId && picked) {
            const cid = getCategoryId(picked);
            if (cid) nextCategoryId = String(cid);
        }

        // category_id が推定/指定できる場合のみセット（できない場合は従来通り全表示）
        if (nextCategoryId && canFilterByCategory) {
            setSelectedCategoryId(String(nextCategoryId));
        }

        if (nextServiceId) {
            setFormData((prev) => ({ ...prev, service_id: String(nextServiceId) }));
        }

        didInitFromQuery.current = true;
    }, [services, canFilterByCategory]);

    // -------------------------
    // ✅ 月内休業日（グレーアウト）取得
    // GET /api/reservations/month-schedule?year=YYYY&month=MM
    // -------------------------
    const fetchMonthSchedule = async (year, month) => {
        if (loadedYm.year === year && loadedYm.month === month) return;

        let aborted = false;

        try {
            const params = new URLSearchParams({
                year: String(year),
                month: String(month),
            });

            const res = await fetch(`/api/reservations/month-schedule?${params.toString()}`, {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });

            if (!res.ok) return;

            const data = await res.json();

            // ✅ 互換：{closed_dates: []} / {days:[{date,is_closed,...}]} / {schedule:[{date,is_closed,...}]}
            let list = [];
            if (Array.isArray(data?.closed_dates)) {
                list = data.closed_dates;
            } else if (Array.isArray(data?.days)) {
                list = data.days
                    .filter((x) => x?.is_closed)
                    .map((x) => x?.date)
                    .filter(Boolean);
            } else if (Array.isArray(data?.schedule)) {
                list = data.schedule
                    .filter((x) => x?.is_closed)
                    .map((x) => x?.date)
                    .filter(Boolean);
            }

            const nextSet = new Set(list);

            if (!aborted) {
                setClosedDates(nextSet);
                setLoadedYm({ year, month });
            }
        } catch (err) {
            console.error("月内スケジュール取得に失敗:", err);
        }

        return () => {
            aborted = true;
        };
    };

    useEffect(() => {
        const y = date.getFullYear();
        const m = date.getMonth() + 1;
        fetchMonthSchedule(y, m);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date]);

    // -------------------------
    // ✅ 空き枠チェックAPI
    // GET /api/reservations/check?date=YYYY-MM-DD&service_id=ID
    // -------------------------
    useEffect(() => {
        if (!isServiceSelected) {
            setAvailableSlotsByApi([]);
            setSelectedTime("");
            setAvailabilityError(false);
            setAvailabilityMessage("");
            return;
        }

        let aborted = false;

        async function fetchAvailability() {
            setAvailabilityLoading(true);
            setAvailabilityError(false);
            setAvailabilityMessage("");

            try {
                const params = new URLSearchParams({
                    date: selectedYmd,
                    service_id: String(formData.service_id),
                });

                const res = await fetch(`/api/reservations/check?${params.toString()}`, {
                    headers: { Accept: "application/json" },
                    cache: "no-store",
                });

                if (!res.ok) {
                    const errorData = await res.json().catch(() => ({}));
                    if (!aborted) {
                        setAvailableSlotsByApi([]);
                        setAvailabilityError(true);
                        setAvailabilityMessage(
                            typeof errorData?.message === "string" && errorData.message
                                ? errorData.message
                                : "空き状況の取得に失敗しました。"
                        );
                        setSelectedTime("");
                    }
                    return;
                }

                const data = await res.json();

                if (!aborted) {
                    setAvailabilityMessage(typeof data?.message === "string" ? data.message : "");
                }

                // ✅ 互換：available_slots / availableSlots / slots
                const slots = Array.isArray(data?.available_slots)
                    ? data.available_slots
                    : Array.isArray(data?.availableSlots)
                        ? data.availableSlots
                        : Array.isArray(data?.slots)
                            ? data.slots
                            : [];

                const normalized = slots
                    .map((s) => ({
                        start: toHHmm(s?.start),
                        end: toHHmm(s?.end),
                    }))
                    .filter((s) => s.start && s.end);

                if (!aborted) {
                    setAvailableSlotsByApi(normalized);

                    setSelectedTime((current) => {
                        if (!current) return "";
                        return normalized.some((x) => x.start === current) ? current : "";
                    });
                }
            } catch (err) {
                console.error("予約可能時間チェックに失敗:", err);
                if (!aborted) {
                    setAvailableSlotsByApi([]);
                    setAvailabilityError(true);
                    setAvailabilityMessage("空き状況の取得に失敗しました。");
                    setSelectedTime("");
                }
            } finally {
                if (!aborted) setAvailabilityLoading(false);
            }
        }

        fetchAvailability();

        return () => {
            aborted = true;
        };
    }, [selectedYmd, formData.service_id, isServiceSelected]);

    // -------------------------
    // カレンダー無効化（過去日 + 休業日）
    // -------------------------
    const tileDisabled = ({ date: d }) => {
        if (isPastDay(d)) return true;
        const ymd = toYmd(d);
        return closedDates.has(ymd);
    };

    // ✅ 追加：カテゴリ変更
    const handleCategoryChange = (e) => {
        const value = e.target.value;
        setSelectedCategoryId(value);
        setMessage("");
        setErrors((prev) => {
            if (!prev?.service_id) return prev;
            const next = { ...prev };
            delete next.service_id;
            return next;
        });

        // service がカテゴリ外になったらクリア
        setFormData((prev) => {
            if (!prev.service_id) return prev;
            const picked = services.find((s) => String(s.id) === String(prev.service_id));
            const cid = getCategoryId(picked);
            if (!value) return prev; // 「すべて」なら維持
            if (cid && String(cid) === String(value)) return prev;
            return { ...prev, service_id: "" };
        });

        setSelectedTime("");
        setAvailableSlotsByApi([]);
        setAvailabilityError(false);
        setAvailabilityMessage("");
    };

    // 入力変更
    const handleChange = (e) => {
        const { name, value } = e.target;

        setFormData((prev) => ({ ...prev, [name]: value }));

        // ✅ 追加：変更したフィールドのエラーは消す（firstError が次に進みやすい）
        setErrors((prev) => {
            if (!prev || typeof prev !== "object") return prev;
            if (!prev[name]) return prev;
            const next = { ...prev };
            delete next[name];
            return next;
        });

        if (name === "service_id") {
            setSelectedTime("");
            setMessage("");
            setAvailableSlotsByApi([]);
            setAvailabilityError(false);
            setAvailabilityMessage("");

            // ✅ 追加：service 選択からカテゴリを追随（category_id が取れる時だけ）
            if (canFilterByCategory) {
                const picked = services.find((s) => String(s.id) === String(value));
                const cid = getCategoryId(picked);
                if (cid) {
                    setSelectedCategoryId(String(cid));
                }
            }
        }
    };

    // 送信
    const handleSubmit = async (e) => {
        e.preventDefault();

        setMessage("");
        setErrors({});

        // ✅ ローカルチェック（noValidate なので必須はJSで担保）
        const localErrors = {};

        if (!String(formData.name || "").trim()) localErrors.name = ["お名前は必須です。"];

        if (!String(formData.email || "").trim()) {
            localErrors.email = ["メールアドレスは必須です。"];
        } else {
            const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(formData.email).trim());
            if (!emailOk) localErrors.email = ["メールアドレスの形式が正しくありません。"];
        }

        if (!String(formData.maker || "").trim()) localErrors.maker = ["メーカーは必須です。"];
        if (!String(formData.car_model || "").trim()) localErrors.car_model = ["車種は必須です。"];

        if (!String(formData.phone || "").trim()) {
            localErrors.phone = ["電話番号は必須です。"];
        } else {
            const phoneOk = /^\d{10,11}$/.test(String(formData.phone).trim());
            if (!phoneOk) localErrors.phone = ["電話番号は10〜11桁の数字で入力してください（ハイフンなし）。"];
        }

        if (!formData.service_id) localErrors.service_id = ["メニューを選択してください。"];
        if (!selectedTime) localErrors.start_time = ["時間を選択してください。"];

        if (Object.keys(localErrors).length > 0) {
            setErrors(localErrors);
            return;
        }

        const pickedSlot = availableSlotsByApi.find((s) => toHHmm(s?.start) === selectedTime);

        if (!pickedSlot) {
            setErrors({ start_time: ["その時間帯は選択できません。別の時間を選択してください。"] });
            return;
        }
        if (!pickedSlot.end) {
            setErrors({ start_time: ["終了時刻を取得できません。別の時間を選択してください。"] });
            return;
        }

        const payload = {
            ...formData,
            date: selectedYmd,
            start_time: selectedTime,
            end_time: pickedSlot.end,
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
                await response.json();
                setErrors({});
                setMessage("✅ ご予約が完了しました！メールをご確認ください。");

                setSelectedTime("");
                setFormData({
                    name: "",
                    phone: "",
                    service_id: "",
                    email: "",
                    maker: "",
                    car_model: "",
                    // ✅ 追加（コース）
                    course: "",
                    notes: "",
                });
                setAvailableSlotsByApi([]);
                setAvailabilityError(false);
                setAvailabilityMessage("");

                // ✅ 追加：カテゴリも初期化（直アクセス時の挙動を維持したいなら空に戻す）
                setSelectedCategoryId("");
            } else {
                const errorData = await response.json().catch(() => ({}));

                // ✅ 422（バリデーション）は errors を拾って firstError() で表示
                if (response.status === 422) {
                    if (errorData?.errors && typeof errorData.errors === "object") {
                        setErrors(errorData.errors);
                    }
                    setMessage(errorData?.message || "入力内容に誤りがあります。");
                    return;
                }

                // ✅ 409（重複）などは message だけ表示
                setMessage(errorData.message || "⚠️ 予約に失敗しました。");
            }
        } catch (err) {
            console.error("送信エラー:", err);
            setMessage("⚠️ サーバー通信エラーが発生しました。");
        }
    };

    // 戻る
    const handleBack = () => {
        window.location.href = "/menu_price";
    };

    return (
        <main className="reservation-main">
            <div className="reservation-back">
                <button type="button" onClick={handleBack} className="reservation-back-button">
                    前のページに戻る
                </button>
            </div>

            <h1 className="reservation-title">ご予約フォーム</h1>

            <form onSubmit={handleSubmit} className="reservation-form-card" noValidate>
                {/* ✅ A案：メッセージは「フォーム内の先頭」に統一 */}
                {topMessage && (
                    <p
                        className={`reservation-message ${topMessageIsSuccess ? "reservation-message--success" : "reservation-message--error"
                            }`}
                        aria-live="polite"
                    >
                        {topMessage}
                    </p>
                )}

                {/* 名前（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        お名前 <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                        placeholder="例）山田 太郎"
                    />
                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("name") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("name")}
                        </p>
                    )}
                </div>

                {/* メール（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メールアドレス <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                        placeholder="例）example@gmail.com"
                    />
                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("email") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("email")}
                        </p>
                    )}
                </div>

                {/* メーカー（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メーカー <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="text"
                        name="maker"
                        value={formData.maker}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                        placeholder="例）トヨタ"
                    />
                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("maker") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("maker")}
                        </p>
                    )}
                </div>

                {/* 車種（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        車種 <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="text"
                        name="car_model"
                        value={formData.car_model}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                        placeholder="例）プリウス"
                    />
                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("car_model") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("car_model")}
                        </p>
                    )}
                </div>

                {/* 電話番号（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        電話番号 <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                        placeholder="例）09012345678 ハイフンはなしで入力してください"
                    />
                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("phone") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("phone")}
                        </p>
                    )}
                </div>

                {/* ✅ 追加：カテゴリ（category_id が取れる時だけ表示） */}
                {canFilterByCategory && (
                    <div className="reservation-field">
                        <label className="reservation-label">コース</label>
                        <select
                            value={selectedCategoryId}
                            onChange={handleCategoryChange}
                            className="reservation-select"
                        >
                            <option value="">すべて</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                {/* メニュー（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メニュー <span className="reservation-required">必須</span>
                    </label>
                    <select
                        name="service_id"
                        value={formData.service_id}
                        onChange={handleChange}
                        required
                        className="reservation-select"
                    >
                        <option value="">選択してください</option>
                        {filteredServices.map((service) => (
                            <option key={service.id} value={service.id}>
                                {service.name}（¥{service.price} / {service.duration_minutes}分）
                            </option>
                        ))}
                    </select>

                    {/* ✅ 最小追加：項目別エラー */}
                    {fieldError("service_id") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("service_id")}
                        </p>
                    )}

                    {/* ✅ 追加：指定リンク文言（メニュー直下） */}
                    <a
                        href="https://www.keepercoating.jp/lineup/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="reservation-menu-help"
                    >
                        メニューの詳細をご確認後メニューを選択してください。
                    </a>
                </div>

                {/* 日付（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        ご希望日 <span className="reservation-required">必須</span>
                    </label>
                    <div className="reservation-calendar-wrapper">
                        <div className="reservation-calendar">
                            <Calendar
                                onChange={(d) => {
                                    const nextDate = Array.isArray(d) ? d?.[0] : d;
                                    if (!nextDate) return;

                                    // ✅ 同じ日付を再クリックした場合は、空き枠をクリアしない
                                    const nextYmd = toYmd(nextDate);
                                    if (nextYmd === selectedYmd) return;

                                    setDate(nextDate);
                                    setSelectedTime("");
                                    setMessage("");
                                    setErrors((prev) => {
                                        if (!prev || typeof prev !== "object") return prev;
                                        const next = { ...prev };
                                        delete next.date;
                                        delete next.start_time;
                                        delete next.end_time;
                                        return next;
                                    });
                                    setAvailableSlotsByApi([]);
                                    setAvailabilityError(false);
                                    setAvailabilityMessage("");
                                }}
                                value={date}
                                tileDisabled={tileDisabled}
                                onActiveStartDateChange={({ activeStartDate }) => {
                                    if (!activeStartDate) return;
                                    fetchMonthSchedule(activeStartDate.getFullYear(), activeStartDate.getMonth() + 1);
                                }}
                            />
                        </div>
                        <p className="reservation-date-text">選択された日付: {date.toLocaleDateString()}</p>

                        {/* ✅ 最小追加：項目別エラー（date） */}
                        {fieldError("date") && (
                            <p className="reservation-message reservation-message--error" aria-live="polite">
                                {fieldError("date")}
                            </p>
                        )}
                    </div>
                </div>

                {/* 時間（必須） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        ご希望時間 <span className="reservation-required">必須</span>
                    </label>

                    <div className="reservation-time-wrapper">
                        {!isServiceSelected ? (
                            <p className="reservation-time-note">※ メニューを選択すると空き時間が表示されます</p>
                        ) : (
                            <>
                                <p className="reservation-time-note">
                                    {availabilityLoading
                                        ? "空き状況を確認中..."
                                        : availabilityError
                                            ? availabilityMessage
                                                ? `※ ${availabilityMessage}`
                                                : "※ 空き状況の取得に失敗しました（時間は表示しません）"
                                            : availabilityMessage
                                                ? `※ ${availabilityMessage}`
                                                : "空き時間のみ表示しています。"}
                                </p>

                                {displayTimes.length === 0 ? (
                                    <p className="reservation-time-note">
                                        {availabilityLoading ? "" : availabilityError ? "" : "※ 空き時間がありません"}
                                    </p>
                                ) : (
                                    <div className="reservation-time-grid">
                                        {displayTimes.map((time) => (
                                            <button
                                                type="button"
                                                key={time}
                                                onClick={() => {
                                                    setSelectedTime(time);
                                                    setMessage("");
                                                    setErrors((prev) => {
                                                        if (!prev?.start_time) return prev;
                                                        const next = { ...prev };
                                                        delete next.start_time;
                                                        return next;
                                                    });
                                                }}
                                                className={`reservation-time-button ${selectedTime === time ? "reservation-time-button--selected" : ""
                                                    }`}
                                            >
                                                {time}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </>
                        )}

                        {selectedTime && <p className="reservation-selected-time">選択された時間: {selectedTime}</p>}

                        {/* ✅ 最小追加：項目別エラー（start_time） */}
                        {fieldError("start_time") && (
                            <p className="reservation-message reservation-message--error" aria-live="polite">
                                {fieldError("start_time")}
                            </p>
                        )}
                    </div>
                </div>

                {/* 備考（任意） */}
                <div className="reservation-field">
                    <label className="reservation-label">備考</label>
                    <textarea
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                        rows={3}
                        className="reservation-textarea"
                        placeholder="ご要望・補足があればご記入ください（任意）"
                    />
                    {/* ✅ 最小追加：項目別エラー（notes） */}
                    {fieldError("notes") && (
                        <p className="reservation-message reservation-message--error" aria-live="polite">
                            {fieldError("notes")}
                        </p>
                    )}
                </div>

                {/* ✅ 追加：注意文（予約ボタン直前に固定表示） */}
                <p className="reservation-note" aria-live="polite">
                    ※予約通知メールが迷惑メールフォルダに入る場合がありますので、ご注意ください。
                </p>

                {/* ✅ 追加：フォーム先頭と同じメッセージを「予約する」ボタン直前にも表示 */}
                {topMessage && (
                    <p
                        className={`reservation-message ${topMessageIsSuccess ? "reservation-message--success" : "reservation-message--error"
                            }`}
                        aria-live="polite"
                    >
                        {topMessage}
                    </p>
                )}

                <button type="submit" className="reservation-submit-button">
                    予約する
                </button>
            </form>
        </main>
    );
}
