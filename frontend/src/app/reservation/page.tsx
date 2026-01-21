// frontend/src/app/reservation/page.tsx
"use client";

import { Fragment, useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import Calendar from "react-calendar";

type Service = {
    id: number;
    name: string;
    description?: string | null;
    duration_minutes?: number | null;
    category_id?: number | string | null;
    category_name?: string | null;
    category?: { id?: number | string | null; name?: string | null } | null;
};

type AvailableSlot = { start: string; end: string };
type BusinessHour = { open_time: string; close_time: string; is_closed?: boolean };

type LaravelValidationErrors = Record<string, string[] | string>;

type FormData = {
    name: string;
    phone: string;
    service_id: string;
    email: string;
    maker: string;
    car_model: string;
    course: string;
    notes: string;
};

type CalendarValue = Date | Date[] | null;

/**
 * "HH:MM" or "HH:MM:SS" -> "HH:MM"
 */
function toHHmm(value: unknown): string {
    if (!value) return "";
    const str = String(value);

    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) return str.slice(0, 5);

    const d = new Date(str);
    if (Number.isNaN(d.getTime())) return "";
    const h = String(d.getHours()).padStart(2, "0");
    const m = String(d.getMinutes()).padStart(2, "0");
    return `${h}:${m}`;
}

/** ローカル日付 -> "YYYY-MM-DD" */
function toYmd(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function isPastDay(d: Date): boolean {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const target = new Date(d);
    target.setHours(0, 0, 0, 0);

    return target < today;
}

/** "HH:mm" -> minutes */
function hhmmToMinutes(hhmm: unknown): number | null {
    if (!hhmm || typeof hhmm !== "string") return null;
    const m = hhmm.match(/^(\d{2}):(\d{2})$/);
    if (!m) return null;
    const h = Number(m[1]);
    const min = Number(m[2]);
    if (!Number.isFinite(h) || !Number.isFinite(min)) return null;
    return h * 60 + min;
}

/** minutes -> "HH:mm" */
function minutesToHHmm(total: number): string {
    const h = Math.floor(total / 60);
    const m = total % 60;
    return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}

/**
 * 営業時間(open〜close)・施術時間(duration)から「開始可能枠」を生成（15分刻み）
 */
function generateStartTimes(
    openHHmm: string,
    closeHHmm: string,
    durationMinutes: number,
    stepMinutes = 15
): string[] {
    const open = hhmmToMinutes(openHHmm);
    const close = hhmmToMinutes(closeHHmm);
    const dur = Number(durationMinutes);

    if (open == null || close == null) return [];
    if (!Number.isFinite(dur) || dur <= 0) return [];

    const lastStart = close - dur;
    if (lastStart < open) return [];

    const result: string[] = [];
    for (let t = open; t <= lastStart; t += stepMinutes) result.push(minutesToHHmm(t));
    return result;
}

/** 表示用：open〜close を15分刻みで並べる（closeも含める） */
function generateDisplayTimes(openHHmm: string, closeHHmm: string, stepMinutes = 15): string[] {
    const open = hhmmToMinutes(openHHmm);
    const close = hhmmToMinutes(closeHHmm);
    if (open == null || close == null) return [];
    if (close < open) return [];

    const result: string[] = [];
    for (let t = open; t <= close; t += stepMinutes) result.push(minutesToHHmm(t));
    return result;
}

function getCategoryId(service: Service | null): string | null {
    if (!service) return null;
    if (service.category_id != null) return String(service.category_id);
    if (service.category?.id != null) return String(service.category.id);
    return null;
}

function getCategoryName(service: Service | null): string {
    if (!service) return "";
    if (typeof service.category_name === "string" && service.category_name) return service.category_name;
    if (service.category?.name) return String(service.category.name);
    return "";
}

function firstError(errors: LaravelValidationErrors | null): string {
    if (!errors || typeof errors !== "object") return "";

    const order = ["name", "email", "maker", "car_model", "phone", "service_id", "date", "start_time", "end_time", "notes"];

    for (const key of order) {
        const v = (errors as any)[key];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
    }

    for (const k of Object.keys(errors)) {
        const v = (errors as any)[k];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
    }

    return "";
}

/** オプションを notes に埋め込む */
function buildNotesWithOption(userNotes: string, optionValues: string[] | string): string {
    const base = String(userNotes || "").trim();
    const listRaw = Array.isArray(optionValues) ? optionValues : [optionValues];

    const list = listRaw.map((x) => String(x || "").trim()).filter(Boolean);
    const normalized = list.length === 0 ? ["なし"] : list.includes("なし") ? ["なし"] : list;

    const optText = normalized.join("、");
    const line = `【オプション】${optText}`;
    return base ? `${line}\n${base}` : line;
}

/**
 * ✅ Sanctum(cookie) 用：CSRF Cookie を取得
 * gateway が /sanctum を Laravel に流している前提
 */
async function ensureCsrfCookie(): Promise<void> {
    await fetch("/sanctum/csrf-cookie", {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
        cache: "no-store",
    }).catch(() => { });
}

/**
 * ✅ Sanctum(cookie) 用：XSRF-TOKEN cookie をヘッダに載せるためのヘルパー
 * - Laravel は XSRF-TOKEN cookie と X-XSRF-TOKEN ヘッダが一致しているかで CSRF を検証します
 */
function getCookie(name: string): string {
    const part = document.cookie
        .split("; ")
        .find((row) => row.startsWith(`${name}=`));
    return part ? part.split("=").slice(1).join("=") : "";
}

function getXsrfTokenHeaderValue(): string {
    const v = getCookie("XSRF-TOKEN");
    if (!v) return "";
    try {
        return decodeURIComponent(v);
    } catch {
        return v;
    }
}

export default function ReservationPage() {
    const router = useRouter();

    // ✅ 表示時刻の上限（営業時間に関係なく）
    const DEBUG = useMemo(() => new URLSearchParams(window.location.search).get("debug") === "1", []);
    const DEBUG_TIME = "18:30";

    const HARD_CLOSE_HHMM = "18:30";
    const HARD_LAST_START_HHMM = "18:30";
    const MIN_LEAD_MINUTES = 60;

    const [date, setDate] = useState<Date>(new Date());
    const [selectedTime, setSelectedTime] = useState<string>("");

    const [formData, setFormData] = useState<FormData>({
        name: "",
        phone: "",
        service_id: "",
        email: "",
        maker: "",
        car_model: "",
        course: "",
        notes: "",
    });

    const OPTION_ITEMS = useMemo(() => ["なし", "室内清掃", "内窓拭き", "ガラス油膜除去", "ガラスコーティング"], []);
    const [selectedOptions, setSelectedOptions] = useState<string[]>(["なし"]);
    const [, setSelectedOption] = useState<string>("なし");

    const toggleOption = (opt: string) => {
        setSelectedOptions((prev) => {
            const current = Array.isArray(prev) ? prev : [];
            const has = current.includes(opt);

            if (opt === "なし") return ["なし"];

            const withoutNone = current.filter((x) => x !== "なし");

            if (has) {
                const next = withoutNone.filter((x) => x !== opt);
                return next.length ? next : ["なし"];
            }

            return [...withoutNone, opt];
        });
    };

    const [services, setServices] = useState<Service[]>([]);

    const [availabilityLoading, setAvailabilityLoading] = useState<boolean>(false);
    const [availabilityError, setAvailabilityError] = useState<boolean>(false);
    const [availableSlotsByApi, setAvailableSlotsByApi] = useState<AvailableSlot[]>([]);
    const [availabilityMessage, setAvailabilityMessage] = useState<string>("");
    const [businessHour, setBusinessHour] = useState<BusinessHour | null>(null);

    const [message, setMessage] = useState<string>("");
    const [errors, setErrors] = useState<LaravelValidationErrors>({});

    const [closedDates, setClosedDates] = useState<Set<string>>(new Set());
    const [loadedYm, setLoadedYm] = useState<{ year: number | null; month: number | null }>({ year: null, month: null });

    const [selectedCategoryId, setSelectedCategoryId] = useState<string>("");
    const didInitFromQuery = useRef<boolean>(false);

    const [hideCourseMenuSelectors, setHideCourseMenuSelectors] = useState<boolean>(false);
    const [availabilityNonce, setAvailabilityNonce] = useState<number>(0);

    const selectedYmd = useMemo(() => toYmd(date), [date]);
    const isServiceSelected = !!formData.service_id;

    const selectedService = useMemo(() => {
        if (!Array.isArray(services) || !formData.service_id) return null;
        return services.find((s) => String(s.id) === String(formData.service_id)) || null;
    }, [services, formData.service_id]);

    const serviceDurationMinutes = useMemo(() => {
        const v = Number((selectedService as any)?.duration_minutes);
        return Number.isFinite(v) && v > 0 ? v : 0;
    }, [selectedService]);

    const availableStartTimes = useMemo(() => {
        if (!isServiceSelected) return [];
        if (!Array.isArray(availableSlotsByApi) || availableSlotsByApi.length === 0) return [];
        return availableSlotsByApi.map((s) => toHHmm(s?.start)).filter(Boolean);
    }, [isServiceSelected, availableSlotsByApi]);

    const availableStartSet = useMemo(() => new Set(availableStartTimes), [availableStartTimes]);

    const timeItems = useMemo(() => {
        if (DEBUG) {
            const openDbg = toHHmm(businessHour?.open_time) || "09:00";
            const closeDbg = toHHmm(businessHour?.close_time) || HARD_CLOSE_HHMM;
            const dbgStarts = generateStartTimes(openDbg, closeDbg, serviceDurationMinutes, 15);
            const lastDbg = dbgStarts.length ? dbgStarts[dbgStarts.length - 1] : null;

            const todayYmd = toYmd(new Date());
            const isToday = selectedYmd === todayYmd;

            const now = new Date();
            const nowMin = now.getHours() * 60 + now.getMinutes();
            const leadCutoffMin = nowMin + Number(MIN_LEAD_MINUTES || 0);

            const m = hhmmToMinutes(DEBUG_TIME);
            console.log("[timeItems debug]", {
                selectedYmd,
                todayYmd,
                isToday,
                now: now.toString(),
                nowMin,
                leadCutoffMin,
                debugTime: DEBUG_TIME,
                debugTimeMin: m,
                inApi: availableStartSet.has(DEBUG_TIME),
                leadOk: !isToday ? true : m != null && m > leadCutoffMin,
                businessHour,
                dbg_last_possible_start: lastDbg,
            });
        }

        if (!isServiceSelected) return [];
        if (!serviceDurationMinutes) return [];

        const hardLastStartMin = hhmmToMinutes(HARD_LAST_START_HHMM);

        const applyHardLastStart = (times: string[]) => {
            if (hardLastStartMin == null) return times;
            return times.filter((t) => {
                const m = hhmmToMinutes(t);
                return m != null && m <= hardLastStartMin;
            });
        };

        const todayYmd = toYmd(new Date());
        const isToday = selectedYmd === todayYmd;
        const now = new Date();
        const nowMin = now.getHours() * 60 + now.getMinutes();
        const leadCutoffMin = nowMin + Number(MIN_LEAD_MINUTES || 0);

        const isLeadTimeOk = (t: string) => {
            if (!isToday) return true;
            const m = hhmmToMinutes(t);
            return m != null && m > leadCutoffMin;
        };

        if (availabilityError) {
            const uniq = Array.from(new Set(applyHardLastStart(availableStartTimes)));
            return uniq.map((t) => ({ time: t, available: availableStartSet.has(t) && isLeadTimeOk(t) }));
        }

        const openRaw = toHHmm(businessHour?.open_time) || "09:00";

        const apiCloseRaw = toHHmm(businessHour?.close_time) || HARD_CLOSE_HHMM;
        const apiCloseMin = hhmmToMinutes(apiCloseRaw);
        const hardCloseMin = hhmmToMinutes(HARD_CLOSE_HHMM);

        const cappedClose =
            apiCloseMin != null && hardCloseMin != null ? minutesToHHmm(Math.min(apiCloseMin, hardCloseMin)) : HARD_CLOSE_HHMM;

        const allDisplay = generateDisplayTimes(openRaw, cappedClose, 15);

        const uniq = Array.from(new Set(applyHardLastStart(allDisplay)));
        return uniq.map((t) => ({ time: t, available: availableStartSet.has(t) && isLeadTimeOk(t) }));
    }, [
        DEBUG,
        businessHour,
        serviceDurationMinutes,
        isServiceSelected,
        availableStartTimes,
        availableStartSet,
        availabilityError,
        selectedYmd,
        MIN_LEAD_MINUTES,
        HARD_CLOSE_HHMM,
        HARD_LAST_START_HHMM,
        DEBUG_TIME,
    ]);

    const categories = useMemo(() => {
        const map = new Map<string, string>();
        if (!Array.isArray(services)) return [];

        services.forEach((s) => {
            const cid = getCategoryId(s);
            if (!cid) return;
            const name = getCategoryName(s);
            if (!map.has(cid)) map.set(cid, name || `カテゴリ${cid}`);
        });

        return Array.from(map, ([id, name]) => ({ id, name }));
    }, [services]);

    const canFilterByCategory = categories.length > 0;

    const filteredServices = useMemo(() => {
        if (!Array.isArray(services)) return [];
        if (!canFilterByCategory) return services;
        if (!selectedCategoryId) return services;
        return services.filter((s) => getCategoryId(s) === String(selectedCategoryId));
    }, [services, selectedCategoryId, canFilterByCategory]);

    const topMessage = useMemo(() => {
        const fe = firstError(errors);
        if (fe) return fe;
        return message || "";
    }, [errors, message]);

    const topMessageIsSuccess = useMemo(() => {
        if (firstError(errors)) return false;
        return typeof message === "string" && message.includes("✅");
    }, [errors, message]);

    const fieldError = (field: string): string => {
        const v = (errors as any)?.[field];
        if (Array.isArray(v) && v.length > 0) return String(v[0]);
        if (typeof v === "string" && v) return v;
        return "";
    };

    // -------------------------
    // サービス一覧取得
    // -------------------------
    useEffect(() => {
        (async () => {
            try {
                const res = await fetch("/api/services", { headers: { Accept: "application/json" }, cache: "no-store" });
                if (!res.ok) return;
                const data = (await res.json()) as Service[];
                setServices(Array.isArray(data) ? data : []);
            } catch (err) {
                console.error("サービス一覧の取得に失敗:", err);
            }
        })();
    }, []);

    // ✅ URLクエリ初期反映
    useEffect(() => {
        if (didInitFromQuery.current) return;
        if (!Array.isArray(services) || services.length === 0) return;

        const params = new URLSearchParams(window.location.search);
        const qsServiceId = params.get("service_id");
        const qsCategoryId = params.get("category_id");

        let nextServiceId = qsServiceId ? String(qsServiceId) : "";
        let nextCategoryId = qsCategoryId ? String(qsCategoryId) : "";

        const picked = nextServiceId ? services.find((s) => String(s.id) === String(nextServiceId)) : null;

        if (!picked) {
            nextServiceId = "";
            setHideCourseMenuSelectors(false);
        } else {
            setHideCourseMenuSelectors(!!qsServiceId);
        }

        if (!nextCategoryId && picked) {
            const cid = getCategoryId(picked);
            if (cid) nextCategoryId = String(cid);
        }

        if (nextCategoryId && canFilterByCategory) setSelectedCategoryId(String(nextCategoryId));

        if (nextServiceId && picked) {
            const courseName =
                getCategoryName(picked) ||
                (nextCategoryId && canFilterByCategory ? categories.find((c) => String(c.id) === String(nextCategoryId))?.name || "" : "");

            setFormData((prev) => ({ ...prev, service_id: String(nextServiceId), course: courseName }));
        }

        didInitFromQuery.current = true;
    }, [services, canFilterByCategory, categories]);

    // -------------------------
    // 月内休業日取得
    // -------------------------
    const fetchMonthSchedule = async (year: number, month: number) => {
        if (loadedYm.year === year && loadedYm.month === month) return;

        try {
            const params = new URLSearchParams({ year: String(year), month: String(month) });
            const res = await fetch(`/api/reservations/month-schedule?${params.toString()}`, {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });
            if (!res.ok) return;

            const data = await res.json();

            let list: string[] = [];
            if (Array.isArray(data?.closed_dates)) {
                list = data.closed_dates;
            } else if (Array.isArray(data?.days)) {
                list = data.days.filter((x: any) => x?.is_closed).map((x: any) => x?.date).filter(Boolean);
            } else if (Array.isArray(data?.schedule)) {
                list = data.schedule.filter((x: any) => x?.is_closed).map((x: any) => x?.date).filter(Boolean);
            }

            setClosedDates(new Set(list));
            setLoadedYm({ year, month });
        } catch (err) {
            console.error("月内スケジュール取得に失敗:", err);
        }
    };

    useEffect(() => {
        fetchMonthSchedule(date.getFullYear(), date.getMonth() + 1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date]);

    // -------------------------
    // 空き枠チェック
    // -------------------------
    useEffect(() => {
        if (!isServiceSelected) {
            setAvailableSlotsByApi([]);
            setBusinessHour(null);
            setSelectedTime("");
            setAvailabilityError(false);
            setAvailabilityMessage("");
            return;
        }

        let aborted = false;

        (async () => {
            setAvailabilityLoading(true);
            setAvailabilityError(false);
            setAvailabilityMessage("");

            try {
                const params = new URLSearchParams({ date: selectedYmd, service_id: String(formData.service_id) });

                const res = await fetch(`/api/reservations/check?${params.toString()}`, {
                    headers: { Accept: "application/json" },
                    cache: "no-store",
                });

                if (!res.ok) {
                    const errorData = await res.json().catch(() => ({}));
                    if (!aborted) {
                        setAvailableSlotsByApi([]);
                        setBusinessHour(null);
                        setAvailabilityError(true);
                        setAvailabilityMessage(errorData?.message || "空き状況の取得に失敗しました。");
                        setSelectedTime("");
                    }
                    return;
                }

                const data = await res.json();

                if (!aborted) setAvailabilityMessage(typeof data?.message === "string" ? data.message : "");

                const bh = data?.business_hour || data?.businessHour || null;
                if (!aborted) {
                    if (bh) {
                        setBusinessHour({
                            open_time: toHHmm(bh.open_time),
                            close_time: toHHmm(bh.close_time),
                            is_closed: !!bh.is_closed,
                        });
                    } else {
                        setBusinessHour(null);
                    }
                }

                const slotsRaw: any[] = Array.isArray(data?.available_slots)
                    ? data.available_slots
                    : Array.isArray(data?.availableSlots)
                        ? data.availableSlots
                        : Array.isArray(data?.slots)
                            ? data.slots
                            : [];

                const normalized: AvailableSlot[] = slotsRaw
                    .map((s) => ({ start: toHHmm(s?.start), end: toHHmm(s?.end) }))
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
                    setBusinessHour(null);
                    setAvailabilityError(true);
                    setAvailabilityMessage("空き状況の取得に失敗しました。");
                    setSelectedTime("");
                }
            } finally {
                if (!aborted) setAvailabilityLoading(false);
            }
        })();

        return () => {
            aborted = true;
        };
    }, [selectedYmd, formData.service_id, isServiceSelected, availabilityNonce, DEBUG]);

    // ✅ 空き枠更新で無効なら解除
    useEffect(() => {
        if (!selectedTime) return;

        const hardLastStartMin = hhmmToMinutes(HARD_LAST_START_HHMM);
        const selMin = hhmmToMinutes(selectedTime);

        if (hardLastStartMin != null && selMin != null && selMin > hardLastStartMin) {
            setSelectedTime("");
            return;
        }

        const todayYmd = toYmd(new Date());
        if (selectedYmd === todayYmd) {
            const now = new Date();
            const nowMin = now.getHours() * 60 + now.getMinutes();
            const leadCutoffMin = nowMin + Number(MIN_LEAD_MINUTES || 0);

            if (selMin != null && selMin <= leadCutoffMin) {
                setSelectedTime("");
                return;
            }
        }

        if (!availableStartSet.has(selectedTime)) setSelectedTime("");
    }, [availableStartSet, selectedTime, selectedYmd, MIN_LEAD_MINUTES, HARD_LAST_START_HHMM]);

    // カレンダー無効化
    const tileDisabled = ({ date: d }: { date: Date }) => {
        if (isPastDay(d)) return true;
        return closedDates.has(toYmd(d));
    };

    const handleCategoryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value;
        setSelectedCategoryId(value);
        setMessage("");

        setErrors((prev) => {
            const next = { ...(prev || {}) };
            delete (next as any).service_id;
            return next;
        });

        const courseName = value ? categories.find((c) => String(c.id) === String(value))?.name || "" : "";
        setFormData((prev) => ({ ...prev, course: courseName }));

        setFormData((prev) => {
            if (!prev.service_id) return prev;
            const picked = services.find((s) => String(s.id) === String(prev.service_id)) || null;
            const cid = getCategoryId(picked);
            if (!value) return prev;
            if (cid && String(cid) === String(value)) return prev;
            return { ...prev, service_id: "", course: "" };
        });

        setSelectedTime("");
        setAvailableSlotsByApi([]);
        setBusinessHour(null);
        setAvailabilityError(false);
        setAvailabilityMessage("");
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;

        setFormData((prev) => ({ ...prev, [name]: value }));

        setErrors((prev) => {
            const next = { ...(prev || {}) };
            if ((next as any)[name]) delete (next as any)[name];
            return next;
        });

        if (name === "service_id") {
            setSelectedTime("");
            setMessage("");
            setAvailableSlotsByApi([]);
            setBusinessHour(null);
            setAvailabilityError(false);
            setAvailabilityMessage("");

            const picked = services.find((s) => String(s.id) === String(value)) || null;
            const courseName = getCategoryName(picked) || "";

            setFormData((prev) => ({ ...prev, service_id: value, course: courseName }));

            if (canFilterByCategory) {
                const cid = getCategoryId(picked);
                if (cid) setSelectedCategoryId(String(cid));
            }

            setHideCourseMenuSelectors(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        setMessage("");
        setErrors({});

        const localErrors: LaravelValidationErrors = {};

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

        if (selectedTime) {
            const hardLastStartMin = hhmmToMinutes(HARD_LAST_START_HHMM);
            const selMin = hhmmToMinutes(selectedTime);
            if (hardLastStartMin != null && selMin != null && selMin > hardLastStartMin) {
                localErrors.start_time = ["予約できる開始時刻は18:30までです。"];
            }
        }

        if (selectedTime && !(localErrors as any).start_time) {
            const todayYmd = toYmd(new Date());
            if (selectedYmd === todayYmd) {
                const now = new Date();
                const nowMin = now.getHours() * 60 + now.getMinutes();
                const leadCutoffMin = nowMin + Number(MIN_LEAD_MINUTES || 0);
                const selMin = hhmmToMinutes(selectedTime);
                if (selMin != null && selMin <= leadCutoffMin) {
                    localErrors.start_time = ["当日のご予約は開始時刻の1時間前まで可能です。"];
                }
            }
        }

        const notesWithOption = buildNotesWithOption(formData.notes, selectedOptions);
        if (notesWithOption.length > 500) localErrors.notes = ["備考はオプションを含めて500文字以内で入力してください。"];

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

        const courseForSend = String(formData.course || "").trim() || getCategoryName(selectedService);

        const payload = {
            ...formData,
            course: courseForSend,
            date: selectedYmd,
            start_time: selectedTime,
            end_time: pickedSlot.end,
            notes: notesWithOption,
        };

        try {
            // ✅ Next では meta csrf-token が無いので Sanctum 方式で CSRF Cookie を取る
            await ensureCsrfCookie();

            // ✅ 追加：XSRF-TOKEN(cookie) -> X-XSRF-TOKEN(header) を付与（これが無いと TokenMismatch になりやすい）
            const xsrf = getXsrfTokenHeaderValue();

            const res = await fetch("/api/reservations", {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    ...(xsrf ? { "X-XSRF-TOKEN": xsrf } : {}),
                },
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                await res.json().catch(() => ({}));
                setErrors({});
                setMessage("✅ ご予約が完了しました！メールをご確認ください。");

                const keepServiceId = hideCourseMenuSelectors ? formData.service_id : "";
                const keepCourse = hideCourseMenuSelectors ? courseForSend : "";
                const keepCategoryId = hideCourseMenuSelectors ? selectedCategoryId : "";

                setSelectedTime("");
                setSelectedOptions(["なし"]);
                setSelectedOption("なし");

                setFormData({
                    name: "",
                    phone: "",
                    service_id: keepServiceId,
                    email: "",
                    maker: "",
                    car_model: "",
                    course: keepCourse,
                    notes: "",
                });

                setSelectedCategoryId(hideCourseMenuSelectors ? keepCategoryId : "");
                setAvailabilityNonce((n) => n + 1);
                return;
            }

            const errorData = await res.json().catch(() => ({}));

            if (res.status === 422) {
                if (errorData?.errors && typeof errorData.errors === "object") setErrors(errorData.errors);
                setMessage(errorData?.message || "入力内容に誤りがあります。");
                return;
            }

            setMessage(errorData?.message || "⚠️ 予約に失敗しました。");
        } catch (err) {
            console.error("送信エラー:", err);
            setMessage("⚠️ サーバー通信エラーが発生しました。");
        }
    };

    const handleBack = () => {
        // 目的に応じて切り替えOK
        // router.back();
        router.push("/menu");
    };

    function TextWithBreaks({ text }: { text: string }) {
        const lines = text.split(/\r\n|\n|\r/);
        return (
            <>
                {lines.map((line, i) => (
                    <Fragment key={i}>
                        {line}
                        {i < lines.length - 1 ? <br /> : null}
                    </Fragment>
                ))}
            </>
        );
    }

    return (
        <main className="reservation-main">
            <div className="reservation-back">
                <button type="button" onClick={handleBack} className="reservation-back-button">
                    前のページに戻る
                </button>
            </div>

            <h1 className="reservation-title">ご予約フォーム</h1>

            <form onSubmit={handleSubmit} className="reservation-form-card" noValidate>
                {topMessage && (
                    <p
                        className={`reservation-message ${topMessageIsSuccess ? "reservation-message--success" : "reservation-message--error"}`}
                        aria-live="polite"
                    >
                        {topMessage}
                    </p>
                )}

                {/* 名前 */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        お名前 <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        className="reservation-input"
                        placeholder="例）山田 太郎"
                    />
                    {fieldError("name") && <p className="reservation-message reservation-message--error">{fieldError("name")}</p>}
                </div>

                {/* メール */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メールアドレス <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        className="reservation-input"
                        placeholder="例）example@gmail.com"
                    />
                    {fieldError("email") && <p className="reservation-message reservation-message--error">{fieldError("email")}</p>}
                </div>

                {/* 電話 */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        電話番号 <span className="reservation-required">必須</span>
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        className="reservation-input"
                        placeholder="例）09012345678 ハイフンはなしで入力してください"
                    />
                    {fieldError("phone") && <p className="reservation-message reservation-message--error">{fieldError("phone")}</p>}
                </div>

                {/* メーカー */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        メーカー <span className="reservation-required">必須</span>
                    </label>
                    <input type="text" name="maker" value={formData.maker} onChange={handleChange} className="reservation-input" placeholder="例）トヨタ" />
                    {fieldError("maker") && <p className="reservation-message reservation-message--error">{fieldError("maker")}</p>}
                </div>

                {/* 車種 */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        車種 <span className="reservation-required">必須</span>
                    </label>
                    <input type="text" name="car_model" value={formData.car_model} onChange={handleChange} className="reservation-input" placeholder="例）プリウス" />
                    {fieldError("car_model") && <p className="reservation-message reservation-message--error">{fieldError("car_model")}</p>}
                </div>

                {/* コース/メニュー（menuから来たら非表示） */}
                {!hideCourseMenuSelectors && (
                    <>
                        {canFilterByCategory && (
                            <div className="reservation-field">
                                <label className="reservation-label">コース</label>
                                <select value={selectedCategoryId} onChange={handleCategoryChange} className="reservation-select">
                                    <option value="">すべて</option>
                                    {categories.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        <div className="reservation-field">
                            <label className="reservation-label">
                                メニュー名 <span className="reservation-required">必須</span>
                            </label>
                            <select name="service_id" value={formData.service_id} onChange={handleChange} className="reservation-select">
                                <option value="">選択してください</option>
                                {filteredServices.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.name}（{Number(service.duration_minutes || 0)}分）
                                    </option>
                                ))}
                            </select>

                            {fieldError("service_id") && <p className="reservation-message reservation-message--error">{fieldError("service_id")}</p>}

                            <a href="https://www.keepercoating.jp/lineup/" target="_blank" rel="noopener noreferrer" className="reservation-menu-help">
                                メニューの詳細をご確認後メニューを選択してください。
                            </a>
                        </div>
                    </>
                )}

                {/* オプション */}
                <div className="reservation-field">
                    <label className="reservation-label">オプション</label>
                    <div className="reservation-time-grid">
                        {OPTION_ITEMS.map((opt) => (
                            <button
                                key={opt}
                                type="button"
                                onClick={() => toggleOption(opt)}
                                className={`reservation-time-button ${selectedOptions.includes(opt) ? "reservation-time-button--selected" : ""}`}
                                aria-pressed={selectedOptions.includes(opt)}
                            >
                                {opt}
                            </button>
                        ))}
                    </div>
                </div>

                {/* 日付 */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        ご希望日 <span className="reservation-required">必須</span>
                    </label>

                    <div className="reservation-calendar-wrapper">
                        <div className="reservation-calendar">
                            <Calendar
                                onChange={(v: CalendarValue) => {
                                    const nextDate = Array.isArray(v) ? v?.[0] : v;
                                    if (!nextDate) return;

                                    const nextYmd = toYmd(nextDate);
                                    if (nextYmd === selectedYmd) return;

                                    setDate(nextDate);
                                    setSelectedTime("");
                                    setMessage("");
                                    setErrors((prev) => {
                                        const next = { ...(prev || {}) };
                                        delete (next as any).date;
                                        delete (next as any).start_time;
                                        delete (next as any).end_time;
                                        return next;
                                    });

                                    setAvailableSlotsByApi([]);
                                    setBusinessHour(null);
                                    setAvailabilityError(false);
                                    setAvailabilityMessage("");
                                }}
                                value={date}
                                tileDisabled={tileDisabled as any}
                                onActiveStartDateChange={({ activeStartDate }: any) => {
                                    if (!activeStartDate) return;
                                    fetchMonthSchedule(activeStartDate.getFullYear(), activeStartDate.getMonth() + 1);
                                }}
                            />
                        </div>

                        <p className="reservation-date-text">選択された日付: {date.toLocaleDateString()}</p>

                        {fieldError("date") && <p className="reservation-message reservation-message--error">{fieldError("date")}</p>}
                    </div>
                </div>

                {/* 時間 */}
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
                                                : "※ 空き状況の取得に失敗しました。"
                                            : availabilityMessage
                                                ? `※ ${availabilityMessage}`
                                                : "○：予約可 / ×：予約不可（選択できません）"}
                                </p>

                                {timeItems.length === 0 ? (
                                    <p className="reservation-time-note">{availabilityLoading ? "" : availabilityError ? "" : "※ 予約枠がありません"}</p>
                                ) : (
                                    <div className="reservation-time-grid">
                                        {timeItems.map(({ time, available }) => {
                                            const isSelected = selectedTime === time;
                                            const disabled = !available;

                                            return (
                                                <button
                                                    type="button"
                                                    key={time}
                                                    disabled={disabled}
                                                    onClick={() => {
                                                        if (!available) return;
                                                        setSelectedTime(time);
                                                        setMessage("");
                                                        setErrors((prev) => {
                                                            const next = { ...(prev || {}) };
                                                            delete (next as any).start_time;
                                                            return next;
                                                        });
                                                    }}
                                                    className={`reservation-time-button ${isSelected ? "reservation-time-button--selected" : ""} ${disabled ? "reservation-time-button--disabled" : ""
                                                        }`}
                                                    aria-disabled={disabled}
                                                >
                                                    <div>
                                                        <div>{time}</div>
                                                        <div>{available ? "○" : "×"}</div>
                                                        <div>{available ? "" : ""}</div>
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </>
                        )}

                        {selectedTime && <p className="reservation-selected-time">選択された時間: {selectedTime}</p>}

                        {fieldError("start_time") && <p className="reservation-message reservation-message--error">{fieldError("start_time")}</p>}
                    </div>
                </div>

                {/* 備考 */}
                <div className="reservation-field">
                    <label className="reservation-label">備考</label>
                    <textarea name="notes" value={formData.notes} onChange={handleChange} rows={3} className="reservation-textarea" placeholder="ご要望・補足があればご記入ください（任意）" />
                    {fieldError("notes") && <p className="reservation-message reservation-message--error">{fieldError("notes")}</p>}
                </div>

                <p className="reservation-note" aria-live="polite">
                    ※予約通知メールが迷惑メールフォルダに入る場合がありますので、ご注意ください。
                </p>

                {topMessage && (
                    <p className={`reservation-message ${topMessageIsSuccess ? "reservation-message--success" : "reservation-message--error"}`} aria-live="polite">
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
