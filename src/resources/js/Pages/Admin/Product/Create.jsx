// /resources/js/Pages/Admin/Product/Create.jsx
import React, { useState } from "react";
import { useForm, router } from "@inertiajs/react";
import { motion } from "framer-motion";
import { route } from "ziggy-js";

// CSS モジュール
import "../../../../css/pages/admin/product/create.css";

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        price: "",
        description: "",
        image: null,
        stock: 0,
    });

    const [preview, setPreview] = useState(null);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        setData("image", file);
        if (file) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route("admin.products.store"), {
            onSuccess: () => {
                reset();
                setPreview(null);
            },
        });
    };

    return (
        <motion.div
            className="admin-product-create-page"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="admin-product-create-container">
                <h1 className="admin-product-create-title">商品登録</h1>

                <form
                    onSubmit={handleSubmit}
                    className="admin-product-create-form"
                    encType="multipart/form-data"
                >
                    {/* 商品名 */}
                    <div className="admin-product-create-field">
                        <label className="admin-product-create-label">
                            商品名
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="admin-product-create-input"
                            placeholder="商品名を入力"
                        />
                        {errors.name && (
                            <p className="admin-product-create-error">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    {/* 価格 */}
                    <div className="admin-product-create-field">
                        <label className="admin-product-create-label">
                            価格
                        </label>
                        <input
                            type="number"
                            name="price"
                            value={data.price}
                            onChange={(e) => setData("price", e.target.value)}
                            className="admin-product-create-input"
                            placeholder="価格を入力"
                        />
                        {errors.price && (
                            <p className="admin-product-create-error">
                                {errors.price}
                            </p>
                        )}
                    </div>

                    {/* 商品説明 */}
                    <div className="admin-product-create-field">
                        <label className="admin-product-create-label">
                            商品説明
                        </label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={(e) =>
                                setData("description", e.target.value)
                            }
                            className="admin-product-create-textarea"
                            placeholder="商品の説明を入力"
                            rows={5}
                        />
                        {errors.description && (
                            <p className="admin-product-create-error">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    {/* 商品画像 */}
                    <div className="admin-product-create-field">
                        <label className="admin-product-create-label">
                            商品画像
                        </label>
                        <input
                            type="file"
                            onChange={handleFileChange}
                            className="admin-product-create-file"
                            accept="image/*"
                        />
                        {preview && (
                            <div className="admin-product-create-preview-wrapper">
                                <div className="admin-product-create-preview-inner">
                                    <img
                                        src={preview}
                                        alt="preview"
                                        className="admin-product-create-preview-image"
                                    />
                                </div>
                            </div>
                        )}
                        {errors.image && (
                            <p className="admin-product-create-error">
                                {errors.image}
                            </p>
                        )}
                    </div>

                    {/* 在庫数 */}
                    <div className="admin-product-create-field">
                        <label
                            htmlFor="stock"
                            className="admin-product-create-label"
                        >
                            在庫数
                        </label>
                        <input
                            type="number"
                            name="stock"
                            id="stock"
                            value={data.stock}
                            onChange={(e) =>
                                setData("stock", e.target.value)
                            }
                            className="admin-product-create-input"
                            min="0"
                        />
                        {errors.stock && (
                            <p className="admin-product-create-error">
                                {errors.stock}
                            </p>
                        )}
                    </div>

                    {/* ボタンエリア */}
                    <div className="admin-product-create-actions">
                        <button
                            type="button"
                            onClick={() =>
                                router.visit(route("admin.products.index"))
                            }
                            className="admin-product-create-button admin-product-create-button--back"
                        >
                            戻る
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="admin-product-create-button admin-product-create-button--submit"
                        >
                            {processing ? "登録中..." : "登録"}
                        </button>
                    </div>
                </form>
            </div>
        </motion.div>
    );
}
