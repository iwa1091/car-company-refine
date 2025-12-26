import { Head, Link, useForm } from "@inertiajs/react";
import "../../../css/pages/auth/authentication.css";

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/login");
    };

    return (
        <>
            <Head title="ログイン" />

            <div className="authentication-page">
                <div className="authentication-container">
                    <form onSubmit={submit} className="authenticate-form">
                        <h1 className="page__title">ログイン</h1>

                        {/* ---- メールアドレス ---- */}
                        <div className="form-group">
                            <label htmlFor="email" className="entry__name">
                                メールアドレス
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                className="input input--short"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                required
                                autoComplete="email"
                                autoFocus
                            />

                            {errors.email && (
                                <span className="form__error">{errors.email}</span>
                            )}
                        </div>

                        {/* ---- パスワード ---- */}
                        <div className="form-group">
                            <label htmlFor="password" className="entry__name">
                                パスワード
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                className="input input--short"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                                required
                                autoComplete="current-password"
                            />

                            {errors.password && (
                                <span className="form__error">{errors.password}</span>
                            )}
                        </div>

                        {/* ---- ログインボタン ---- */}
                        <button
                            type="submit"
                            className="btn btn--big"
                            disabled={processing}
                        >
                            ログインする
                        </button>

                        {/* ---- パスワードを忘れた方 ---- */}
                        <div className="auth-help-block">
                            <p className="auth-help-text">
                                パスワードをお忘れの方は、以下のリンクから
                                再設定メールを送信できます。
                            </p>
                            <Link href="/forgot-password" className="link">
                                パスワードをお忘れの方はこちら
                            </Link>
                        </div>

                        {/* ---- メールアドレスを忘れた方 ---- */}
                        <div className="auth-help-block">
                            <p className="auth-help-text">
                                登録したメールアドレスが分からない場合は、
                                ご本人確認のためお手数ですが、
                                お問い合わせフォームよりご連絡ください。
                            </p>
                            <Link href="/contact" className="link">
                                お問い合わせフォームへ
                            </Link>
                        </div>

                        {/* ---- 会員登録へ ---- */}
                        <Link href="/register" className="link">
                            会員登録はこちら
                        </Link>
                    </form>
                </div>
            </div>
        </>
    );
}
