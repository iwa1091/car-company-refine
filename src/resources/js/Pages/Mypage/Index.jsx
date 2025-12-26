// /resources/js/Pages/Mypage/Index.jsx
import { Head, Link, useForm, usePage } from "@inertiajs/react";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/mypage/index.css";

export default function Mypage({
    user,
    pastReservations,
    pastOrders,
    upcomingReservations,
}) {
    // Inertia から flash メッセージ & バリデーションエラーを取得
    const { flash, errors } = usePage().props;

    // 予約番号紐付けフォーム用の useForm
    const { data, setData, post, processing } = useForm({
        reservation_code: "",
    });

    const handleLinkSubmit = (e) => {
        e.preventDefault();
        // セッションの CSRF を自動で付けて POST
        post("/mypage/link-reservation");
    };

    return (
        <div className="mypage-root">
            <Head title="マイページ" />

            {/* -----------------------------------
                ページヘッダー
            ----------------------------------- */}
            <header className="mypage-header">
                <h1 className="mypage-header-title">
                    ようこそ、{user?.name} さん
                </h1>
                <p className="mypage-header-subtitle">
                    ご予約履歴やお気に入りメニューをいつでも確認できます
                </p>
            </header>

            {/* -----------------------------------
                メインコンテンツ
            ----------------------------------- */}
            <main className="mypage-main">
                {/* ================================
                    予約番号紐付けフォーム
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        🔗 予約番号を紐付ける
                    </h2>

                    {/* 成功メッセージ */}
                    {flash?.success && (
                        <p className="mypage-flash-success">
                            {flash.success}
                        </p>
                    )}

                    {/* バリデーションエラー（予約番号） */}
                    {errors?.reservation_code && (
                        <p className="mypage-flash-error">
                            {errors.reservation_code}
                        </p>
                    )}

                    <form
                        onSubmit={handleLinkSubmit}
                        className="mypage-link-form"
                    >
                        <input
                            type="text"
                            name="reservation_code"
                            value={data.reservation_code}
                            onChange={(e) =>
                                setData("reservation_code", e.target.value)
                            }
                            placeholder="予約番号を入力してください"
                            className="mypage-input"
                            required
                        />

                        <button
                            type="submit"
                            className="mypage-primary-button"
                            disabled={processing}
                        >
                            予約を紐付ける
                        </button>
                    </form>
                </section>

                {/* ================================
                    予約中
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        📅 予約中のメニュー
                    </h2>

                    {upcomingReservations?.length ? (
                        upcomingReservations.map((res) => (
                            <div
                                key={res.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {res.service?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    来店日：
                                    {res.date
                                        ? new Date(
                                            res.date
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>
                                <p className="mypage-item-meta">
                                    開始時間：{res.start_time ?? "-"}
                                </p>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            現在予約はありません。
                        </p>
                    )}
                </section>

                {/* ================================
                    過去の予約
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        🕘 過去のメニュー
                    </h2>

                    {pastReservations?.length ? (
                        pastReservations.map((r) => (
                            <div
                                key={r.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {r.service?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    来店日：
                                    {r.date
                                        ? new Date(
                                            r.date
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>

                                <Link
                                    href={`/reservation?repeat=${r.service?.id}`}
                                    className="mypage-inline-link"
                                >
                                    このメニューを再予約 →
                                </Link>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            過去の予約はありません。
                        </p>
                    )}
                </section>

                {/* ================================
                    購入履歴
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        🛍 購入履歴
                    </h2>

                    {pastOrders?.length ? (
                        pastOrders.map((o) => (
                            <div
                                key={o.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {o.product?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    購入日：
                                    {o.ordered_at
                                        ? new Date(
                                            o.ordered_at
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>

                                <a
                                    href={`/online-store/products/${o.product?.id}`}
                                    className="mypage-inline-link"
                                >
                                    再購入 →
                                </a>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            購入履歴はありません。
                        </p>
                    )}
                </section>
            </main>

            {/* -----------------------------------
                トップへ戻る（固定ボタン）
            ----------------------------------- */}
            <a href="/" className="mypage-home-fab">
                ⬆ ホームに戻る
            </a>
        </div>
    );
}
