// frontend/src/app/menu/page.tsx
import Link from "next/link";
import { Fragment } from "react";

type MenuService = {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    is_popular: boolean;
    features: string[];
    image_url: string | null;
};

type MenuCategory = {
    id: number;
    name: string;
    description: string | null;
    services: MenuService[];
};

type MenuResponse = {
    categories: MenuCategory[];
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

async function fetchMenu(): Promise<MenuResponse> {
    // Docker開発では Next(SSR) -> Laravel への到達先が必要
    // docker-compose で NEXT_PUBLIC_BACKEND_ORIGIN=http://nginx を入れている想定
    const backend = (process.env.NEXT_PUBLIC_BACKEND_ORIGIN || "").replace(/\/$/, "");
    const url = backend ? `${backend}/api/public/menu` : "/api/public/menu";

    const res = await fetch(url, {
        // 常に最新（必要なら revalidate に変更してOK）
        cache: "no-store",
        headers: {
            Accept: "application/json",
        },
    });

    if (!res.ok) {
        const text = await res.text().catch(() => "");
        throw new Error(`menu fetch failed: ${res.status} ${text}`);
    }

    return (await res.json()) as MenuResponse;
}

export default async function MenuPage() {
    let data: MenuResponse | null = null;
    let error = "";

    try {
        data = await fetchMenu();
    } catch (e: any) {
        error = e?.message || "読み込みに失敗しました。";
    }

    const categories =
        data?.categories
            ?.map((c) => ({
                ...c,
                services: Array.isArray(c.services) ? c.services : [],
            }))
            .filter((c) => c.services.length > 0) ?? [];

    return (
        <div className="menu-page-container">
            <div className="menu-inner">
                {/* ページヘッダー（このページ固有） */}
                <div className="menu-header">
                    <h1 className="menu-title">メニュー・料金</h1>
                    <p className="menu-description">
                        お客様のご希望に合わせて、様々なメニューをご用意しております。
                        <br />
                    </p>
                </div>

                {error ? (
                    <p className="no-service" style={{ whiteSpace: "pre-wrap" }}>
                        {error}
                    </p>
                ) : null}

                {!error && categories.length === 0 ? (
                    <p className="no-service">現在、登録されているメニューはありません。</p>
                ) : null}

                {!error &&
                    categories.map((category) => (
                        <section className="menu-section" key={category.id}>
                            <h2 className="section-title">{category.name}</h2>

                            {category.description ? (
                                <p className="category-description">{category.description}</p>
                            ) : null}

                            <div className="menu-grid">
                                {category.services.map((service) => (
                                    <div
                                        key={service.id}
                                        className={`menu-card ${service.is_popular ? "menu-card-popular" : ""}`}
                                    >
                                        {service.is_popular ? (
                                            <span className="popular-badge">人気No.1</span>
                                        ) : null}

                                        {service.image_url ? (
                                            <div className="card-image">
                                                {/* Next/Image にすると CSS の object-fit などは調整が必要になるので、互換優先で img */}
                                                <img src={service.image_url} alt={service.name} />

                                                {Array.isArray(service.features) && service.features.length > 0 ? (
                                                    <div className="feature-badges">
                                                        {service.features.map((f, idx) => (
                                                            <span className="feature-badge" key={`${f}-${idx}`}>
                                                                {f}
                                                            </span>
                                                        ))}
                                                    </div>
                                                ) : null}
                                            </div>
                                        ) : null}

                                        <div className="card-header">
                                            <h3 className="card-title">{service.name}</h3>

                                            {service.description ? (
                                                <p className="card-description">
                                                    <TextWithBreaks text={service.description} />
                                                </p>
                                            ) : null}
                                        </div>

                                        <div className="card-content">
                                            <div className="card-price-info">
                                                {/* Blade同様：価格は非表示 */}
                                                <div className="card-duration">
                                                    <span className="duration-text">{service.duration_minutes}分</span>
                                                </div>
                                            </div>

                                            {/* 移行中の想定：/reservation は Laravel 側へ流す（gatewayで /reservation をLaravelへ） */}
                                            <Link
                                                href={`/reservation?service_id=${service.id}`}
                                                className="button-primary btn-reserve"
                                            >
                                                予約する
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))}

                {/* 注意事項 */}
                <section className="notes-section">
                    <h3 className="notes-title">ご注意事項</h3>

                    <div className="notes-grid">
                        <div className="note-item">
                            <p>・料金は車のサイズにより変動します。こちらの詳細からご確認ください。</p>

                            {/* Next 側に置くなら: frontend/public/pdf/menu.pdf */}
                            <a
                                href="/pdf/menu.pdf"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="reservation-menu-help"
                            >
                                車種サイズ表はこちら
                            </a>
                        </div>

                        {/* 2枚目が必要ならここに note-item を追加 */}
                    </div>
                </section>
            </div>
        </div>
    );
}
