import { Head, Link, useForm } from "@inertiajs/react";
import "../../../css/pages/verify/forgot-password.css";

export default function ForgotPassword({ status }) {

    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/forgot-password");
    };

    return (
        <>
            <Head title="パスワード再発行" />

            <div className="forgot-page">
                <div className="forgot-container">

                    <form onSubmit={submit} className="forgot-form">

                        <h1 className="forgot-title">パスワード再発行</h1>

                        {status && (
                            <div className="forgot-status">{status}</div>
                        )}

                        <div className="form-group">
                            <label className="forgot-label">
                                登録しているメールアドレス
                            </label>

                            <input
                                className="forgot-input"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                required
                            />

                            {errors.email && (
                                <span className="forgot-error">{errors.email}</span>
                            )}
                        </div>

                        <button
                            type="submit"
                            className="forgot-btn"
                            disabled={processing}
                        >
                            パスワード再設定メールを送る
                        </button>

                        <Link className="forgot-link" href="/login">
                            ログイン画面へ戻る
                        </Link>

                    </form>

                </div>
            </div>
        </>
    );
}
