// /resources/js/Pages/Admin/Product/Index.jsx
import React from "react";
import { Link, usePage, router } from "@inertiajs/react";
import { motion } from "framer-motion";
import { route } from "ziggy-js"; // ✅ Ziggy v2以降は名前付きインポート

// CSS モジュール（Vite 経由）
// resources/js から見て ../../../css/... が正しい相対パス
import "../../../../css/pages/admin/product/index.css";

export default function Index() {
    const { products, filters } = usePage().props;

    // 検索フォームの送信処理
    const handleSearch = (e) => {
        e.preventDefault();
        const form = e.target;

        router.get(
            route("admin.products.index"),
            {
                keyword: form.keyword.value,
                sort: form.sort.value,
            },
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    };

    return (
        <motion.div
            className="admin-product-page"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="admin-product-container">
                {/* Header */}
                <header className="admin-product-header">
                    <h1 className="admin-product-title">商品管理</h1>
                    <p className="admin-product-lead">
                        オンラインストアで販売する商品の追加、編集、削除を行います。
                        在庫や価格設定を最新に保ちましょう。
                    </p>
                </header>

                {/* 検索・並び替えフォーム */}
                <form
                    onSubmit={handleSearch}
                    className="admin-product-search-form"
                >
                    <div className="admin-product-search-main">
                        <input
                            type="text"
                            name="keyword"
                            placeholder="商品名で検索"
                            defaultValue={filters.keyword || ""}
                            className="admin-product-search-input"
                        />
                        <select
                            name="sort"
                            defaultValue={filters.sort || ""}
                            className="admin-product-search-select"
                        >
                            <option value="">価格で並び替え</option>
                            <option value="high_price">高い順</option>
                            <option value="low_price">低い順</option>
                        </select>
                        <button
                            type="submit"
                            className="admin-product-search-button"
                        >
                            検索
                        </button>
                    </div>

                    {/* 新しい商品追加ボタン */}
                    <Link
                        href={route("admin.products.create")}
                        className="admin-product-create-button"
                    >
                        <i className="fa-solid fa-plus-circle" />
                        <span>新しい商品を追加</span>
                    </Link>
                </form>

                {/* 商品一覧 */}
                <section className="admin-product-grid">
                    {products.data.length > 0 ? (
                        products.data.map((product) => (
                            <Link
                                key={product.id}
                                href={route(
                                    "admin.products.show",
                                    product.id
                                )}
                                className="admin-product-card"
                            >
                                <div className="admin-product-image-wrapper">
                                    <img
                                        src={`/storage/${product.image_path}`}
                                        alt={product.name}
                                        className="admin-product-image"
                                    />
                                </div>
                                <div className="admin-product-card-body">
                                    <h3 className="admin-product-name">
                                        {product.name}
                                    </h3>
                                    <p className="admin-product-excerpt">
                                        {product.description.slice(0, 50)}
                                        ...
                                    </p>
                                    <span className="admin-product-price">
                                        ¥{product.price.toLocaleString()}
                                    </span>
                                    <p
                                        className={
                                            "admin-product-stock " +
                                            (product.stock > 0
                                                ? "is-available"
                                                : "is-soldout")
                                        }
                                    >
                                        {product.stock > 0
                                            ? `在庫: ${product.stock} 点`
                                            : "在庫なし（売り切れ）"}
                                    </p>
                                </div>
                            </Link>
                        ))
                    ) : (
                        <p className="admin-product-empty">
                            商品が見つかりませんでした。
                        </p>
                    )}
                </section>

                {/* ページネーション */}
                <nav className="admin-product-pagination">
                    {products.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || "#"}
                            className={
                                "admin-product-page-link" +
                                (link.active ? " is-active" : "")
                            }
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </nav>
            </div>

            {/* 右下固定の戻るボタン（スマホではアイコンのみ表示） */}
            <Link
                href={route("admin.dashboard")}
                className="admin-product-back-floating"
            >
                <i className="fa-solid fa-arrow-left admin-product-back-icon" />
                <span className="admin-product-back-label">
                    前のページに戻る
                </span>
            </Link>
        </motion.div>
    );
}
