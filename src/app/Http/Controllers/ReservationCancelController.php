<?php

namespace App\Http\Controllers;

use App\Mail\AdminReservationCanceledMail;
use App\Mail\UserReservationCanceledMail;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ReservationCancelController extends Controller
{
    /**
     * メールリンクからのキャンセル確認画面
     * GET /reservations/cancel/{token}
     */
    public function show(string $token)
    {
        $reservation = $this->findByToken($token);

        if (!$reservation) {
            return view('reservations.cancel-result', [
                'ok' => false,
                'message' => 'キャンセルURLが無効、または期限切れです。',
            ]);
        }

        // すでにキャンセル済み
        if ($reservation->status === 'cancelled') {
            return view('reservations.cancel-result', [
                'ok' => true,
                'message' => 'この予約はすでにキャンセルされています。',
            ]);
        }

        // 予約日時が過去ならキャンセル不可（運用に合わせて調整OK）
        if (!$this->isCancelableByTime($reservation)) {
            return view('reservations.cancel-result', [
                'ok' => false,
                'message' => '予約日時を過ぎているためキャンセルできません。',
            ]);
        }

        // 確認画面（理由入力）
        return view('reservations.cancel-confirm', [
            'reservation' => $reservation,
            'token' => $token, // POSTで使う
        ]);
    }

    /**
     * キャンセル確定（理由を保存 + メール通知）
     * POST /reservations/cancel/{token}
     */
    public function cancel(Request $request, string $token)
    {
        $reservation = $this->findByToken($token);

        if (!$reservation) {
            return view('reservations.cancel-result', [
                'ok' => false,
                'message' => 'キャンセルURLが無効、または期限切れです。',
            ]);
        }

        if ($reservation->status === 'cancelled') {
            return view('reservations.cancel-result', [
                'ok' => true,
                'message' => 'この予約はすでにキャンセルされています。',
            ]);
        }

        if (!$this->isCancelableByTime($reservation)) {
            return view('reservations.cancel-result', [
                'ok' => false,
                'message' => '予約日時を過ぎているためキャンセルできません。',
            ]);
        }

        // キャンセル理由（任意 / 必須は運用に合わせて変更）
        $validated = $request->validate([
            // 必須にしたいなら 'required|string|max:500'
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $validated['cancel_reason'] ?? null;

        // ----------------------------
        // キャンセル確定（DB更新）
        // ----------------------------
        $reservation->status = 'cancelled';

        // cancelled_at / cancel_reason / cancel_token_hash が存在する場合のみ更新
        try {
            if (Schema::hasColumn('reservations', 'cancelled_at')) {
                $reservation->cancelled_at = now();
            }
            if (Schema::hasColumn('reservations', 'cancel_reason')) {
                $reservation->cancel_reason = $reason;
            }

            // トークン再利用防止（おすすめ）
            if (Schema::hasColumn('reservations', 'cancel_token_hash')) {
                $reservation->cancel_token_hash = null;
            }

            $reservation->save();
        } catch (\Throwable $e) {
            Log::error('[予約キャンセル DB更新エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
            ]);

            return view('reservations.cancel-result', [
                'ok' => false,
                'message' => 'キャンセル処理中にエラーが発生しました。時間をおいて再度お試しください。',
            ]);
        }

        // ----------------------------
        // メール送信（ユーザー＆管理者）
        // ※ メール失敗でもキャンセル自体は成功扱い
        // ----------------------------
        try {
            // メール本文で service を参照する可能性が高いのでロード
            $reservation->loadMissing('service');

            // ユーザー宛
            Mail::to($reservation->email)->send(new UserReservationCanceledMail($reservation));

            // 管理者宛（config優先 → envフォールバック）
            $adminEmail = config('mail.admin_address');
            $adminName  = config('mail.admin_name', 'Admin');

            if (!$adminEmail) {
                $adminEmail = env('MAIL_ADMIN_ADDRESS', '');
            }
            if (!$adminName) {
                $adminName = env('MAIL_ADMIN_NAME', 'Admin');
            }

            // 文字列化＆整形
            $adminEmail = is_string($adminEmail) ? trim($adminEmail) : '';
            $adminName  = is_string($adminName) ? trim($adminName) : 'Admin';

            // ✅ 管理者宛は必ず「文字列アドレス」で送る（連想配列は使わない）
            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($adminEmail, ($adminName !== '' ? $adminName : null))
                    ->send(new AdminReservationCanceledMail($reservation));
            } else {
                Log::warning('[キャンセル通知] MAIL_ADMIN_ADDRESS is invalid. Admin email skipped.', [
                    'reservation_id' => $reservation->id,
                    'adminEmail'     => $adminEmail,
                    'adminName'      => $adminName,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[キャンセル通知メール送信エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'user_email'     => $reservation->email,
            ]);
        }

        return view('reservations.cancel-result', [
            'ok' => true,
            'message' => 'キャンセルが完了しました。',
        ]);
    }

    /**
     * トークンから予約を特定（DBはハッシュ保存）
     */
    private function findByToken(string $token): ?Reservation
    {
        // 変な値は早期に弾く（軽い防御）
        if (strlen($token) < 20) {
            return null;
        }

        // cancel_token_hash が無いとトークン方式は成立しない
        if (!Schema::hasColumn('reservations', 'cancel_token_hash')) {
            Log::warning('[キャンセル] reservations.cancel_token_hash が存在しません（マイグレーション未反映の可能性）');
            return null;
        }

        $hash = hash('sha256', $token);

        return Reservation::where('cancel_token_hash', $hash)->first();
    }

    /**
     * 予約日時の条件でキャンセル可否を判定
     * - start_time が "H:i(:s)" でも "Y-m-d H:i(:s)" でも落ちないようにする
     */
    private function isCancelableByTime(Reservation $reservation): bool
    {
        // date は cast 済み（Carbon） or 文字列の想定
        $date = $reservation->date instanceof \Carbon\Carbon
            ? $reservation->date->toDateString()
            : (string) $reservation->date;

        $startRaw = trim((string) $reservation->start_time);

        // start_time に日付が混ざっている（datetime）場合はそれを優先して判定
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $startRaw)) {
            try {
                return Carbon::parse($startRaw)->gt(now());
            } catch (\Throwable $e) {
                Log::warning('[キャンセル可否判定] start_time(datetime) parse失敗', [
                    'reservation_id' => $reservation->id,
                    'date' => $date,
                    'start_time' => $startRaw,
                    'error' => $e->getMessage(),
                ]);
                return false; // 安全側（キャンセル不可）
            }
        }

        // time 形式（HH:MM:SS / HH:MM）を想定 → HH:MM に寄せる
        $hhmm = strlen($startRaw) >= 5 ? substr($startRaw, 0, 5) : $startRaw;

        try {
            $start = Carbon::parse($date . ' ' . $hhmm);
            return $start->gt(now());
        } catch (\Throwable $e) {
            Log::warning('[キャンセル可否判定] date+time parse失敗', [
                'reservation_id' => $reservation->id,
                'date' => $date,
                'start_time' => $startRaw,
                'hhmm' => $hhmm,
                'error' => $e->getMessage(),
            ]);
            return false; // 安全側
        }
    }
}
