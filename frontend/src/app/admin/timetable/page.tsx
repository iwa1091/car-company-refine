// /home/ri309/new-app/frontend/src/app/admin/timetable/page.tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";

import { apiJson } from "@/lib/apiFetch";

/**
 * 固定レーン（予約は常に枠1）
 */
const LANES = [
    { id: 1, label: "枠1" },
    { id: 2, label: "枠2" },
    { id: 3, label: "調整枠" },
] as const;

const SLOT_MINUTES = 15;
const SLOT_WIDTH_PX = 44; // 15分1枠の幅（CSSの --slot-width と合わせる）
const LANE_LABEL_WIDTH_PX = 120;

function pad2(n: number): string {
    return String(n).padStart(2, "0");
}

function toYmd(d: Date): string {
    const y = d.getFullYear();
    const m = pad2(d.getMonth() + 1);
    const day = pad2(d.getDate());
    return `${y}-${m}-${day}`;
}

/**
 * ✅ Hydration対策：サーバー/クライアントで「今日」の判定がズレないようTZを固定
 * - Docker/SSR環境がUTCでも、JST基準の日付を安定させる
 */
function todayYmdInTokyo(): string {
    try {
        // en-CA は "YYYY-MM-DD" 形式になるのでそのまま使える
        return new Intl.DateTimeFormat("en-CA", {
            timeZone: "Asia/Tokyo",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        }).format(new Date());
    } catch {
        // Intl が使えない環境の保険（基本ここには来ない想定）
        return toYmd(new Date());
    }
}

function fromYmd(ymd: string): Date {
    const [y, m, d] = String(ymd).split("-").map((v) => parseInt(v, 10));
    return new Date(y, (m || 1) - 1, d || 1);
}

function addDays(ymd: string, delta: number): string {
    const base = fromYmd(ymd);
    base.setDate(base.getDate() + delta);
    return toYmd(base);
}

function extractHHmm(value: unknown): string | null {
    if (!value) return null;
    const s = String(value).trim();
    const m = s.match(/(\d{2}:\d{2})/);
    return m ? m[1] : null;
}

function hhmmToMinutes(hhmm: string | null | undefined): number | null {
    const t = extractHHmm(hhmm);
    if (!t) return null;
    const [hh, mm] = t.split(":").map((v) => parseInt(v, 10));
    if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
    return hh * 60 + mm;
}

function minutesToHHmm(min: number): string {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return `${pad2(h)}:${pad2(m)}`;
}

function clamp(n: number, min: number, max: number): number {
    return Math.max(min, Math.min(max, n));
}

function diffMinutes(startHHmm: string | null, endHHmm: string | null): number | null {
    const s = hhmmToMinutes(startHHmm);
    const e = hhmmToMinutes(endHHmm);
    if (s == null || e == null) return null;
    return e - s;
}

function buildTimeOptions(openHHmm: string, closeHHmm: string): string[] {
    const openMin = hhmmToMinutes(openHHmm);
    const closeMin = hhmmToMinutes(closeHHmm);
    if (openMin == null || closeMin == null) return [];

    const options: string[] = [];
    for (let t = openMin; t <= closeMin - SLOT_MINUTES; t += SLOT_MINUTES) {
        options.push(minutesToHHmm(t));
    }
    return options;
}

function buildDurationOptions(maxMinutes = 600): number[] {
    const opts: number[] = [];
    for (let m = SLOT_MINUTES; m <= maxMinutes; m += SLOT_MINUTES) {
        opts.push(m);
    }
    return opts;
}

type AnyObj = Record<string, any>;

function buildDisplayLines(item: AnyObj): string[] {
    const name = item?.name ? String(item.name) : "";
    const maker = item?.maker ? String(item.maker) : "";
    const carModel = item?.car_model ? String(item.car_model) : "";
    const course = item?.course ? String(item.course) : "";
    const serviceName = item?.service_name
        ? String(item.service_name)
        : item?.menu
            ? String(item.menu)
            : "";

    const car = maker || carModel ? `${maker}${maker && carModel ? " " : ""}${carModel}` : "";
    const lines: string[] = [];
    if (name) lines.push(name);
    if (car) lines.push(car);
    if (course) lines.push(course);
    if (serviceName) lines.push(serviceName);

    return lines;
}

/**
 * ✅ 予約が「キャンセル済み」っぽいかを判定（APIが返すフィールド差異に耐える）
 * - status: "canceled" / "cancelled" / "cancel" 等
 * - canceled_at / deleted_at が埋まっている
 * - is_canceled / is_cancelled が truthy
 */
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
        // それ以外でも "cancel" を含むならキャンセル扱いの可能性が高い
        if (s.includes("cancel")) return true;
    }

    if (r?.canceled_at) return true;
    if (r?.deleted_at) return true;

    if (isTruthy(r?.is_canceled) || isTruthy(r?.is_cancelled)) return true;

    return false;
}

/**
 * API 型（必要最低限）
 */
type BusinessHour = {
    is_closed?: boolean | number;
    open_time?: string;
    close_time?: string;
};

type TimetableReservation = {
    id: number;
    start_time?: string;
    end_time?: string;
    name?: string;
    maker?: string;
    car_model?: string;
    course?: string;
    service_name?: string;
    menu?: string;
    notes?: string;

    // ✅ 追加（キャンセル除外判定用：あっても無くてもOK）
    status?: string | null;
    canceled_at?: string | null;
    deleted_at?: string | null;
    is_canceled?: boolean | number | string | null;
    is_cancelled?: boolean | number | string | null;
};

type TimetableBlock = {
    id: number;
    lane?: number | string;
    start_time?: string;
    end_time?: string;
    name?: string;
    maker?: string;
    car_model?: string;
    course?: string;
    menu?: string;
    notes?: string;
};

type TimetableResponse = {
    business_hour?: BusinessHour;
    businessHour?: BusinessHour;
    reservations?: TimetableReservation[];
    blocks?: TimetableBlock[];
};

/**
 * ✅ 正規化後の表示用型
 * - TimetableReservation/TimetableBlock から start_time/end_time/lane を除外して再定義（intersection 事故防止）
 */
type NormalizedReservation = Omit<TimetableReservation, "start_time" | "end_time"> & {
    type: "reservation";
    lane: 1;
    start_time: string | null;
    end_time: string | null;
};

type NormalizedBlock = Omit<TimetableBlock, "lane" | "start_time" | "end_time"> & {
    type: "block";
    lane: 2 | 3;
    start_time: string | null;
    end_time: string | null;
};

type AnyItem = NormalizedReservation | NormalizedBlock;

export default function AdminTimetablePage() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const initialDate = (searchParams.get("date") || "").slice(0, 10);

    // ✅ Hydration対策：URLにdateが無い場合でも JST基準の「今日」を使う
    const [date, setDate] = useState<string>(initialDate || todayYmdInTokyo());

    // URL の date が変わったら state 同期
    useEffect(() => {
        const next = (searchParams.get("date") || "").slice(0, 10);
        if (next && next !== date) setDate(next);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchParams]);

    const [loading, setLoading] = useState<boolean>(true);
    const [loadError, setLoadError] = useState<string>("");

    const [businessHour, setBusinessHour] = useState<Required<BusinessHour>>({
        is_closed: false,
        open_time: "09:00",
        close_time: "19:30",
    });

    const [reservations, setReservations] = useState<NormalizedReservation[]>([]);
    const [blocks, setBlocks] = useState<NormalizedBlock[]>([]);

    // ブロック作成/編集モーダル
    const [modalOpen, setModalOpen] = useState<boolean>(false);
    const [modalMode, setModalMode] = useState<"create" | "edit">("create");
    const [editingBlockId, setEditingBlockId] = useState<number | null>(null);
    const [modalError, setModalError] = useState<string>("");
    const [saving, setSaving] = useState<boolean>(false);

    const [form, setForm] = useState<{
        lane: number;
        start_time: string;
        duration_minutes: number;
        name: string;
        maker: string;
        car_model: string;
        course: string;
        menu: string;
        notes: string;
    }>({
        lane: 2,
        start_time: "09:00",
        duration_minutes: 60,
        name: "",
        maker: "",
        car_model: "",
        course: "",
        menu: "",
        notes: "",
    });

    const openHHmm = extractHHmm(businessHour?.open_time) || "09:00";
    const closeHHmm = extractHHmm(businessHour?.close_time) || "19:30";
    const isClosed = !!businessHour?.is_closed;

    const openMin = hhmmToMinutes(openHHmm);
    const closeMin = hhmmToMinutes(closeHHmm);

    const slotCount = useMemo(() => {
        if (openMin == null || closeMin == null) return 0;
        const diff = closeMin - openMin;
        if (diff <= 0) return 0;
        return Math.ceil(diff / SLOT_MINUTES);
    }, [openMin, closeMin]);

    const timelineWidthPx = useMemo(() => slotCount * SLOT_WIDTH_PX, [slotCount]);

    const timeLabelHours = useMemo(() => {
        if (openMin == null || closeMin == null) return [];
        const labels: number[] = [];
        const startHour = Math.ceil(openMin / 60) * 60;
        for (let t = startHour; t <= closeMin; t += 60) labels.push(t);

        if (openMin % 60 === 0) {
            const set = new Set(labels);
            set.add(openMin);
            return Array.from(set).sort((a, b) => a - b);
        }
        return labels;
    }, [openMin, closeMin]);

    const timeOptions = useMemo(() => {
        if (isClosed) return [];
        return buildTimeOptions(openHHmm, closeHHmm);
    }, [openHHmm, closeHHmm, isClosed]);

    const durationOptions = useMemo(() => buildDurationOptions(600), []);

    const fetchData = async (ymd: string) => {
        setLoading(true);
        setLoadError("");

        try {
            const data = await apiJson<TimetableResponse>(
                `/api/admin/timetable?date=${encodeURIComponent(ymd)}`,
                { method: "GET" }
            );

            const bh = data?.business_hour || data?.businessHour || {};
            const nextBh: Required<BusinessHour> = {
                is_closed: !!bh.is_closed,
                open_time: extractHHmm(bh.open_time) || "09:00",
                close_time: extractHHmm(bh.close_time) || "19:30",
            };

            // ✅ キャンセル済みっぽい予約は除外して表示しない（APIが該当フィールドを返している場合に効く）
            const nextReservations: NormalizedReservation[] = Array.isArray(data?.reservations)
                ? data!.reservations!
                    .filter((r) => !isCanceledReservationLike(r as AnyObj))
                    .map((r) => ({
                        type: "reservation",
                        // start/end は正規化したいので、Omit 対象以外だけ展開
                        id: r.id,
                        name: r.name,
                        maker: r.maker,
                        car_model: r.car_model,
                        course: r.course,
                        service_name: r.service_name,
                        menu: r.menu,
                        notes: r.notes,

                        // 追加フィールド（あれば持っておく：型整合のため）
                        status: r.status ?? null,
                        canceled_at: r.canceled_at ?? null,
                        deleted_at: r.deleted_at ?? null,
                        is_canceled: r.is_canceled ?? null,
                        is_cancelled: r.is_cancelled ?? null,

                        lane: 1,
                        start_time: extractHHmm(r.start_time),
                        end_time: extractHHmm(r.end_time),
                    }))
                : [];

            const nextBlocks: NormalizedBlock[] = Array.isArray(data?.blocks)
                ? data!.blocks!.map((b) => {
                    const laneNum = Number(b.lane);
                    const lane: 2 | 3 = laneNum === 3 ? 3 : 2;

                    return {
                        type: "block",
                        // start/end/lane は正規化したいので、Omit 対象以外だけ展開
                        id: b.id,
                        name: b.name,
                        maker: b.maker,
                        car_model: b.car_model,
                        course: b.course,
                        menu: b.menu,
                        notes: b.notes,
                        lane,
                        start_time: extractHHmm(b.start_time),
                        end_time: extractHHmm(b.end_time),
                    };
                })
                : [];

            setBusinessHour(nextBh);
            setReservations(nextReservations);
            setBlocks(nextBlocks);
        } catch (e: any) {
            setLoadError("データの取得に失敗しました。API のルート/実装を確認してください。");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData(date);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date]);

    // ✅ 別画面で更新→戻ってきた時に古い表示が残る対策（フォーカス復帰で再取得）
    useEffect(() => {
        const onFocus = () => {
            fetchData(date);
        };

        const onVisible = () => {
            if (document.visibilityState === "visible") {
                fetchData(date);
            }
        };

        window.addEventListener("focus", onFocus);
        document.addEventListener("visibilitychange", onVisible);

        return () => {
            window.removeEventListener("focus", onFocus);
            document.removeEventListener("visibilitychange", onVisible);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date]);

    const goDate = (ymd: string) => {
        setDate(ymd);
        // URL も更新（共有・戻る対応）
        router.replace(`/admin/timetable?date=${encodeURIComponent(ymd)}`);
    };

    const openCreateModal = () => {
        if (isClosed) return;

        setModalMode("create");
        setEditingBlockId(null);
        setModalError("");

        setForm((prev) => ({
            lane: 2,
            start_time: timeOptions[0] || openHHmm,
            duration_minutes: 60,
            name: "",
            maker: "",
            car_model: "",
            course: "",
            menu: "",
            notes: "",
        }));

        setModalOpen(true);
    };

    const openEditModal = (block: NormalizedBlock) => {
        setModalMode("edit");
        setEditingBlockId(block.id);
        setModalError("");

        const dur = diffMinutes(block.start_time, block.end_time);
        setForm({
            lane: Number(block.lane) || 2,
            start_time: extractHHmm(block.start_time) || openHHmm,
            duration_minutes: dur && dur > 0 ? dur : 60,
            name: block.name || "",
            maker: block.maker || "",
            car_model: block.car_model || "",
            course: block.course || "",
            menu: block.menu || "",
            notes: block.notes || "",
        });

        setModalOpen(true);
    };

    const closeModal = () => {
        if (saving) return;
        setModalOpen(false);
        setModalError("");
    };

    const saveBlock = async () => {
        if (saving) return;

        setSaving(true);
        setModalError("");

        try {
            const dur = Number(form.duration_minutes) || 0;
            if (dur <= 0 || dur % SLOT_MINUTES !== 0) {
                setModalError("所要時間は15分刻みで指定してください。");
                return;
            }

            const payload = {
                date,
                lane: Number(form.lane),
                start_time: form.start_time,
                duration_minutes: dur,
                name: form.name || null,
                maker: form.maker || null,
                car_model: form.car_model || null,
                course: form.course || null,
                menu: form.menu || null,
                notes: form.notes || null,
            };

            const isEdit = modalMode === "edit" && editingBlockId;
            const url = isEdit ? `/api/admin/blocks/${editingBlockId}` : `/api/admin/blocks`;
            const method = isEdit ? "PUT" : "POST";

            await apiJson<any>(url, {
                method,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });

            setModalOpen(false);
            await fetchData(date);
        } catch (e: any) {
            const msg =
                e?.data?.message ||
                e?.message ||
                (e?.data?.errors ? Object.values(e.data.errors).flat().join("\n") : "") ||
                "保存に失敗しました。";
            setModalError(msg);
        } finally {
            setSaving(false);
        }
    };

    const deleteBlock = async () => {
        if (saving) return;
        if (!editingBlockId) return;
        if (!confirm("このブロックを削除しますか？")) return;

        setSaving(true);
        setModalError("");

        try {
            await apiJson<any>(`/api/admin/blocks/${editingBlockId}`, { method: "DELETE" });
            setModalOpen(false);
            await fetchData(date);
        } catch {
            setModalError("削除に失敗しました。");
        } finally {
            setSaving(false);
        }
    };

    const deleteReservation = async (reservationId: number) => {
        if (!reservationId) return;
        if (!confirm("この予約を削除しますか？")) return;

        try {
            await apiJson<any>(`/api/admin/reservations/${reservationId}`, { method: "DELETE" });
            await fetchData(date);
        } catch {
            alert("削除に失敗しました。");
        }
    };

    const blocksByLane = useMemo(() => {
        const by = new Map<number, AnyItem[]>();
        for (const lane of LANES) by.set(lane.id, []);

        const all: AnyItem[] = [
            ...reservations.map((r) => ({ ...r, lane: 1 as const, type: "reservation" as const })),
            ...blocks.map((b) => ({ ...b, type: "block" as const })),
        ];

        for (const item of all) {
            const laneId = Number(item.lane) || 1;
            if (!by.has(laneId)) by.set(laneId, []);
            by.get(laneId)!.push(item);
        }

        for (const [k, arr] of by.entries()) {
            arr.sort((a, b) => {
                const am = hhmmToMinutes(a.start_time) ?? 0;
                const bm = hhmmToMinutes(b.start_time) ?? 0;
                return am - bm;
            });
            by.set(k, arr);
        }

        return by;
    }, [reservations, blocks]);

    const renderItemBlock = (item: AnyItem) => {
        if (openMin == null || closeMin == null || slotCount <= 0) return null;

        const s = hhmmToMinutes(item.start_time);
        const e = hhmmToMinutes(item.end_time);
        if (s == null || e == null) return null;

        const startClamped = clamp(s, openMin, closeMin);
        const endClamped = clamp(e, openMin, closeMin);
        const dur = endClamped - startClamped;
        if (dur <= 0) return null;

        const startIndex = Math.floor((startClamped - openMin) / SLOT_MINUTES);
        const span = Math.ceil(dur / SLOT_MINUTES);

        const left = startIndex * SLOT_WIDTH_PX;
        const width = span * SLOT_WIDTH_PX;

        const lines = buildDisplayLines(item);
        const timeLabel = `${extractHHmm(item.start_time) || ""}〜${extractHHmm(item.end_time) || ""}`;
        const isReservation = item.type === "reservation";

        const onClick = () => {
            if (isReservation) {
                router.push(`/admin/reservations/${item.id}/edit`);
                return;
            }
            openEditModal(item as NormalizedBlock);
        };

        return (
            <div
                key={`${item.type}-${item.id}`}
                className={
                    "timetable-block " + (isReservation ? "timetable-block--reservation" : "timetable-block--admin")
                }
                style={{ left: `${left}px`, width: `${width}px` }}
                role="button"
                tabIndex={0}
                onClick={onClick}
                onKeyDown={(e) => {
                    if (e.key === "Enter") onClick();
                }}
                title={lines.join(" / ")}
            >
                <div className="timetable-block__time">{timeLabel}</div>

                <div className="timetable-block__body">
                    {lines.length > 0 ? (
                        lines.map((l, idx) => (
                            <div key={idx} className="timetable-block__line">
                                {l}
                            </div>
                        ))
                    ) : (
                        <div className="timetable-block__line">（内容なし）</div>
                    )}
                </div>

                {isReservation ? (
                    <button
                        type="button"
                        className="timetable-block__delete"
                        onClick={(e) => {
                            e.stopPropagation();
                            deleteReservation(item.id);
                        }}
                        aria-label="予約を削除"
                        title="予約を削除"
                    >
                        ×
                    </button>
                ) : null}
            </div>
        );
    };

    return (
        <div className="admin-timetable-page">
            <div className="admin-timetable-topbar">
                <div className="admin-timetable-back">
                    <Link href="/admin/reservations" className="admin-timetable-back-link">
                        予約カレンダーへ戻る
                    </Link>
                    <Link href="/admin" className="admin-timetable-back-link">
                        ダッシュボード
                    </Link>
                </div>

                <div className="admin-timetable-nav">
                    <button
                        type="button"
                        className="admin-timetable-btn"
                        onClick={() => goDate(addDays(date, -1))}
                        disabled={loading}
                    >
                        ← 前日
                    </button>

                    <div className="admin-timetable-date">
                        <div className="admin-timetable-date__label">日付</div>
                        {/* ✅ 日付ズレ起因のHydrationを避ける（保険） */}
                        <div className="admin-timetable-date__value" suppressHydrationWarning>
                            {date}
                        </div>
                        <div className="admin-timetable-date__sub">
                            {isClosed ? (
                                <span className="admin-timetable-closed">休業日</span>
                            ) : (
                                <span className="admin-timetable-open">
                                    {openHHmm}〜{closeHHmm}
                                </span>
                            )}
                        </div>
                    </div>

                    <button
                        type="button"
                        className="admin-timetable-btn"
                        onClick={() => goDate(addDays(date, 1))}
                        disabled={loading}
                    >
                        翌日 →
                    </button>
                </div>

                <div className="admin-timetable-actions">
                    <button
                        type="button"
                        className="admin-timetable-btn admin-timetable-btn--primary"
                        onClick={openCreateModal}
                        disabled={loading || isClosed}
                        title={isClosed ? "休業日は作成できません" : "管理者ブロックを作成"}
                    >
                        ＋ ブロック作成
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="admin-timetable-loading">読み込み中...</div>
            ) : loadError ? (
                <div className="admin-timetable-error">{loadError}</div>
            ) : (
                <div className="admin-timetable-card">
                    <div className="admin-timetable-hint">
                        <span className="admin-timetable-hint__badge">15分刻み</span>
                        <span className="admin-timetable-hint__text">
                            予約は <b>枠1</b> に自動表示。枠2/調整枠は管理者ブロックで使用します。
                        </span>
                    </div>

                    {isClosed ? (
                        <div className="admin-timetable-closed-panel">
                            この日は休業日です（タイムテーブルは表示しません）。
                        </div>
                    ) : (
                        <div className="timetable-scroll">
                            {/* ヘッダー（時間） */}
                            <div className="timetable-header">
                                <div className="timetable-header__lane" style={{ width: `${LANE_LABEL_WIDTH_PX}px` }} />
                                <div
                                    className="timetable-header__timeline"
                                    style={{
                                        width: `${timelineWidthPx}px`,
                                        ["--slot-width" as any]: `${SLOT_WIDTH_PX}px`,
                                    }}
                                >
                                    {timeLabelHours.map((min) => {
                                        if (openMin == null) return null;
                                        if (min < openMin || (closeMin != null && min > closeMin)) return null;

                                        const offsetSlots = Math.round((min - openMin) / SLOT_MINUTES);
                                        const leftPx = offsetSlots * SLOT_WIDTH_PX;
                                        const hhmm = minutesToHHmm(min);

                                        return (
                                            <div key={min} className="timetable-hour-label" style={{ left: `${leftPx}px` }}>
                                                {hhmm}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* レーン行 */}
                            <div className="timetable-body">
                                {LANES.map((lane) => {
                                    const items = blocksByLane.get(lane.id) || [];
                                    return (
                                        <div key={lane.id} className="timetable-row">
                                            <div className="timetable-lane" style={{ width: `${LANE_LABEL_WIDTH_PX}px` }}>
                                                <div className="timetable-lane__title">{lane.label}</div>
                                                <div className="timetable-lane__sub">{lane.id === 1 ? "予約" : "ブロック"}</div>
                                            </div>

                                            <div
                                                className="timetable-track"
                                                style={{
                                                    width: `${timelineWidthPx}px`,
                                                    ["--slot-width" as any]: `${SLOT_WIDTH_PX}px`,
                                                }}
                                            >
                                                {items.map(renderItemBlock)}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* モーダル */}
            {modalOpen ? (
                <div className="timetable-modal-overlay" onMouseDown={closeModal}>
                    <div
                        className="timetable-modal"
                        onMouseDown={(e) => e.stopPropagation()}
                        role="dialog"
                        aria-modal="true"
                    >
                        <div className="timetable-modal__header">
                            <div className="timetable-modal__title">
                                {modalMode === "edit" ? "ブロック編集" : "ブロック作成"}
                            </div>
                            <button
                                type="button"
                                className="timetable-modal__close"
                                onClick={closeModal}
                                disabled={saving}
                                aria-label="閉じる"
                            >
                                ×
                            </button>
                        </div>

                        <div className="timetable-modal__body">
                            {modalError ? <div className="timetable-modal__error">{modalError}</div> : null}

                            <div className="timetable-form-grid">
                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">レーン</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.lane}
                                        onChange={(e) => setForm((p) => ({ ...p, lane: Number(e.target.value) }))}
                                        disabled={saving}
                                    >
                                        <option value={2}>枠2</option>
                                        <option value={3}>調整枠</option>
                                    </select>
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">開始</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.start_time}
                                        onChange={(e) => setForm((p) => ({ ...p, start_time: e.target.value }))}
                                        disabled={saving}
                                    >
                                        {timeOptions.map((t) => (
                                            <option key={t} value={t}>
                                                {t}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">所要時間</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.duration_minutes}
                                        onChange={(e) =>
                                            setForm((p) => ({ ...p, duration_minutes: Number(e.target.value) }))
                                        }
                                        disabled={saving}
                                    >
                                        {durationOptions.map((m) => (
                                            <option key={m} value={m}>
                                                {m}分
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">名前</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.name}
                                        onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）田中 太郎"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">メーカー</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.maker}
                                        onChange={(e) => setForm((p) => ({ ...p, maker: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）トヨタ"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">車種</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.car_model}
                                        onChange={(e) => setForm((p) => ({ ...p, car_model: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）プリウス"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">コース</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.course}
                                        onChange={(e) => setForm((p) => ({ ...p, course: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）スタンダード"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">メニュー</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.menu}
                                        onChange={(e) => setForm((p) => ({ ...p, menu: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）洗車 / 施工 / 打合せ など"
                                    />
                                </label>

                                <label className="timetable-form-field timetable-form-field--full">
                                    <span className="timetable-form-label">備考</span>
                                    <textarea
                                        className="timetable-form-textarea"
                                        value={form.notes}
                                        onChange={(e) => setForm((p) => ({ ...p, notes: e.target.value }))}
                                        disabled={saving}
                                        rows={3}
                                        placeholder="必要に応じて"
                                    />
                                </label>
                            </div>
                        </div>

                        <div className="timetable-modal__footer">
                            {modalMode === "edit" ? (
                                <button
                                    type="button"
                                    className="admin-timetable-btn admin-timetable-btn--danger"
                                    onClick={deleteBlock}
                                    disabled={saving}
                                >
                                    削除
                                </button>
                            ) : (
                                <span />
                            )}

                            <div className="timetable-modal__footer-right">
                                <button
                                    type="button"
                                    className="admin-timetable-btn"
                                    onClick={closeModal}
                                    disabled={saving}
                                >
                                    キャンセル
                                </button>
                                <button
                                    type="button"
                                    className="admin-timetable-btn admin-timetable-btn--primary"
                                    onClick={saveBlock}
                                    disabled={saving}
                                >
                                    {saving ? "保存中..." : "保存"}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
