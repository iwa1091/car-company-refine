// /resources/js/Pages/Admin/Product/Show.jsx
import React from "react";
import { Link, usePage, router } from "@inertiajs/react";
import { motion } from "framer-motion";
import { route } from "ziggy-js";

// CSS モジュール
import "../../../../css/pages/admin/product/show.css";

/**
 * 商品詳細ページ (Admin/Product/Show.jsx)
 * - 商品の内容確認、編集・削除ボタンを配置
 */
export default function Show() {
    const { product, flash } = usePage().props;

    const handleDelete = () => {
        if (confirm("この商品を削除してもよろしいですか？")) {
            router.delete(route("admin.products.destroy", product.id), {
                onSuccess: () => {
                    alert("商品を削除しました。");
                },
            });
        }
    };

    return (
        <motion.div
            className="admin-product-show-page"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="admin-product-show-container">
                {/* タイトル */}
                <h1 className="admin-product-show-title">商品詳細</h1>

                {/* フラッシュメッセージ */}
                {flash?.success && (
                    <p className="admin-product-show-flash">
                        {flash.success}
                    </p>
                )}

                {/* 商品情報 */}
                <div className="admin-product-show-main">
                    {/* 画像 */}
                    <div className="admin-product-show-image-wrapper">
                        <img
                            src={`/storage/${product.image_path}`}
                            alt={product.name}
                            className="admin-product-show-image"
                        />
                    </div>

                    {/* 商品名 */}
                    <div className="admin-product-show-block">
                        <h2 className="admin-product-show-label">商品名</h2>
                        <p className="admin-product-show-value">
                            {product.name}
                        </p>
                    </div>

                    {/* 価格 */}
                    <div className="admin-product-show-block">
                        <h2 className="admin-product-show-label">価格</h2>
                        <p className="admin-product-show-price">
                            ¥{Number(product.price).toLocaleString()}
                        </p>
                    </div>

                    {/* 商品説明 */}
                    <div className="admin-product-show-block">
                        <h2 className="admin-product-show-label">
                            商品説明
                        </h2>
                        <p className="admin-product-show-description">
                            {product.description}
                        </p>
                    </div>
                </div>

                {/* ボタン群 */}
                <div className="admin-product-show-actions">
                    {/* 一覧へ戻る */}
                    <Link
                        href={route("admin.products.index")}
                        className="admin-product-show-button admin-product-show-button--back"
                    >
                        一覧へ戻る
                    </Link>

                    {/* 編集・削除 */}
                    <div className="admin-product-show-actions-right">
                        <Link
                            href={route("admin.products.edit", product.id)}
                            className="admin-product-show-button admin-product-show-button--edit"
                        >
                            編集する
                        </Link>

                        <button
                            type="button"
                            onClick={handleDelete}
                            className="admin-product-show-button admin-product-show-button--delete"
                        >
                            削除
                        </button>
                    </div>
                </div>
            </div>
        </motion.div>
    );
}
