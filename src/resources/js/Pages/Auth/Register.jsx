import { Head, Link, useForm } from "@inertiajs/react";

// Blade と同じ CSS を適用
import "../../../css/pages/auth/authentication.css";

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        phone: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/register");
    };

    return (
        <>
            <Head title="会員登録" />

            <div className="authentication-page">
                <div className="authentication-container">
                    <form onSubmit={submit} className="authenticate-form">

                        {/* ---------- タイトル ---------- */}
                        <h1 className="page__title">会員登録</h1>

                        {/* ---------- 名前 ---------- */}
                        <div className="form-group">
                            <label htmlFor="name" className="entry__name">
                                お名前
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                className="input"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                required
                                autoComplete="name"
                            />

                            {errors.name && (
                                <span className="form__error">{errors.name}</span>
                            )}
                        </div>

                        {/* ---------- メール ---------- */}
                        <div className="form-group">
                            <label htmlFor="email" className="entry__name">
                                メールアドレス
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                className="input"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                required
                                autoComplete="email"
                            />

                            {errors.email && (
                                <span className="form__error">{errors.email}</span>
                            )}
                        </div>

                        {/* ---------- 電話番号（任意） ---------- */}
                        <div className="form-group">
                            <label htmlFor="phone" className="entry__name">
                                電話番号（任意）
                            </label>

                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                className="input"
                                placeholder="例：09012345678"
                                pattern="[0-9]{10,11}"
                                inputMode="numeric"
                                value={data.phone}
                                onChange={(e) => setData("phone", e.target.value)}
                            />

                            <small className="text-gray-500" style={{ fontSize: "0.85rem" }}>
                                ※ハイフンなしで入力してください
                            </small>

                            {errors.phone && (
                                <span className="form__error">{errors.phone}</span>
                            )}
                        </div>

                        {/* ---------- パスワード ---------- */}
                        <div className="form-group">
                            <label htmlFor="password" className="entry__name">
                                パスワード
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                className="input"
                                required
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                            />

                            {errors.password && (
                                <span className="form__error">{errors.password}</span>
                            )}
                        </div>

                        {/* ---------- 確認用パスワード ---------- */}
                        <div className="form-group">
                            <label htmlFor="password_confirmation" className="entry__name">
                                確認用パスワード
                            </label>

                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                className="input"
                                required
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData("password_confirmation", e.target.value)}
                            />
                        </div>

                        {/* ---------- 登録ボタン ---------- */}
                        <button
                            className="btn btn--big"
                            type="submit"
                            disabled={processing}
                        >
                            登録する
                        </button>

                        {/* ---------- ログインリンク ---------- */}
                        <Link href="/login" className="link">
                            ログインはこちら
                        </Link>
                    </form>
                </div>
            </div>
        </>
    );
}
