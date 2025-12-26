import { Head, useForm } from "@inertiajs/react";
import "../../../css/pages/verify/reset-password.css";

export default function ResetPassword({ email, token }) {

    const { data, setData, post, processing, errors } = useForm({
        token: token,
        email: email,
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/reset-password");
    };

    return (
        <>
            <Head title="パスワード再設定" />

            <div className="reset-page">
                <div className="reset-container">

                    <form onSubmit={submit} className="reset-form">

                        <h1 className="reset-title">パスワード再設定</h1>

                        {/* メール */}
                        <div className="form-group">
                            <label className="reset-label">メールアドレス</label>
                            <input
                                className="reset-input"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                            />
                            {errors.email && (
                                <span className="reset-error">{errors.email}</span>
                            )}
                        </div>

                        {/* 新パスワード */}
                        <div className="form-group">
                            <label className="reset-label">新しいパスワード</label>
                            <input
                                className="reset-input"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                            />
                            {errors.password && (
                                <span className="reset-error">{errors.password}</span>
                            )}
                        </div>

                        {/* 確認用 */}
                        <div className="form-group">
                            <label className="reset-label">パスワード（確認）</label>
                            <input
                                className="reset-input"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData("password_confirmation", e.target.value)
                                }
                            />
                        </div>

                        <button type="submit" className="reset-btn" disabled={processing}>
                            パスワードを更新する
                        </button>
                    </form>

                </div>
            </div>
        </>
    );
}
