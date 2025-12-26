import { Head, useForm } from "@inertiajs/react";
import "../../../css/pages/verify/verify.css";

export default function VerifyEmail({ status }) {
    // 認証メール再送フォーム
    const { post, processing, reset } = useForm({});

    const resend = (e) => {
        e.preventDefault();
        post("/email/verification-notification", {
            onSuccess: () => {
                // 成功時に status を更新してメッセージを表示
                reset();
                alert("新しい認証リンクが、メールアドレスに送信されました。");
            },
        });
    };

    return (
        <>
            <Head title="メール認証" />

            <div className="verify-page">
                <div className="verify-container">

                    {/* タイトル */}
                    <div className="verify-header">
                        <h1 className="page__title">メール認証はお済みですか？</h1>
                    </div>

                    <div className="verify-content">

                        {/* 成功メッセージ */}
                        {status === "verification-link-sent" && (
                            <div className="alert alert--success" role="alert">
                                新しい認証リンクが、メールアドレスに送信されました。
                            </div>
                        )}

                        <p className="verify-text">
                            このページを閲覧するには、メールアドレスの認証が必要です。
                            もし認証用のメールが届いていない場合は、以下のボタンをクリックして、
                            新しい認証メールを再送信してください。
                        </p>

                        {/* 認証メール再送ボタン */}
                        <form onSubmit={resend} className="resend-form">
                            <button
                                type="submit"
                                className="btn btn--big btn--resend"
                                disabled={processing}
                                aria-busy={processing}
                            >
                                認証メールを再送信する
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
