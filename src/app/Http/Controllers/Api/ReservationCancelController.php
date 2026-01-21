<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ReservationCancelController extends Controller
{
    /**
     * キャンセル確認用：予約内容取得
     * GET /api/reservations/cancel/{token}
     */
    public function show(string $token)
    {
        $reservation = $this->findByToken($token);

        if (!$reservation) {
            return response()->json([
                'message' => 'キャンセルURLが無効、または期限切れです。',
                'token_valid' => false,
            ], 404);
        }

        // すでにキャンセル済みなら 409（Next側で文言出せる）
        if (($reservation->status ?? null) !== 'confirmed') {
            return response()->json([
                'message' => 'この予約はすでにキャンセル済み、またはキャンセルできない状態です。',
                'token_valid' => true,
                'reservation' => $this->toSummary($reservation),
            ], 409);
        }

        return response()->json([
            'token_valid' => true,
            'reservation' => $this->toSummary($reservation),
        ], 200);
    }

    /**
     * キャンセル確定
     * POST /api/reservations/cancel/{token}
     * body: { cancel_reason?: string|null }
     */
    public function cancel(Request $request, string $token)
    {
        $reservation = $this->findByToken($token);

        if (!$reservation) {
            return response()->json([
                'ok' => false,
                'message' => 'キャンセルURLが無効、または期限切れです。',
            ], 404);
        }

        // バリデーション（Bladeと同等）
        $validator = Validator::make($request->all(), [
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'cancel_reason.max' => 'キャンセル理由は500文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $validator->errors(),
            ], 422);
        }

        // すでに confirmed 以外なら二重キャンセル防止
        if (($reservation->status ?? null) !== 'confirmed') {
            return response()->json([
                'ok' => false,
                'message' => 'この予約はすでにキャンセル済み、またはキャンセルできない状態です。',
            ], 409);
        }

        $reason = $request->input('cancel_reason');
        $reason = is_string($reason) ? trim($reason) : null;
        if ($reason === '') $reason = null;

        try {
            DB::transaction(function () use ($reservation, $reason) {
                // ✅ ステータス文字列はプロジェクトの実装に合わせて必要なら変更してください
                // 例：'canceled' を使っている場合はここを 'canceled' に
                $reservation->status = 'cancelled';

                // cancel_reason カラムがあれば保存（なければ無視）
                if (Schema::hasColumn('reservations', 'cancel_reason')) {
                    $reservation->cancel_reason = $reason;
                }

                // cancelled_at があれば保存（なければ無視）
                if (Schema::hasColumn('reservations', 'cancelled_at')) {
                    $reservation->cancelled_at = now();
                }

                // ✅ トークンは使い捨て（再利用防止）
                if (Schema::hasColumn('reservations', 'cancel_token_hash')) {
                    $reservation->cancel_token_hash = null;
                }

                $reservation->save();
            });
        } catch (\Throwable $e) {
            Log::error('[ReservationCancel] cancel failed', [
                'reservation_id' => $reservation->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'キャンセル処理に失敗しました。時間をおいて再度お試しください。',
            ], 500);
        }

        // ✅ キャンセル完了メール送信（Bladeを直接使って送る：他ファイル追加不要）
        try {
            // Blade 側で $reservation->cancel_reason を参照していても表示できるように、カラム有無に関わらず属性を持たせる
            $reservation->setAttribute('cancel_reason', $reason);

            $appName = (string) (config('app.name') ?: '予約システム');

            // 管理者宛（予約作成時と同じキー）
            $adminEmail = config('mail.admin_address');
            $adminName  = config('mail.admin_name', 'Admin');

            if (!$adminEmail) {
                $adminEmail = env('MAIL_ADMIN_ADDRESS');
            }
            if (!$adminName) {
                $adminName = env('MAIL_ADMIN_NAME', 'Admin');
            }

            $adminEmail = is_string($adminEmail) ? trim($adminEmail) : '';
            $adminName  = is_string($adminName) ? trim($adminName) : 'Admin';

            // ユーザー宛（テンプレ：resources/views/emails/user-reservation-canceled.blade.php）
            $userEmail = is_string($reservation->email ?? null) ? trim((string)$reservation->email) : '';
            if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.user-reservation-canceled', [
                    'reservation' => $reservation,
                    'cancel_reason' => $reason,
                ], function ($message) use ($userEmail, $appName) {
                    $message->to($userEmail)
                        ->subject("ご予約をキャンセルしました");
                });
            } else {
                Log::warning('[ReservationCancel] user email is invalid. User mail skipped.', [
                    'reservation_id' => $reservation->id ?? null,
                    'userEmail' => $userEmail,
                ]);
            }

            // 管理者宛（テンプレ：resources/views/emails/admin-reservation-canceled.blade.php）
            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.admin-reservation-canceled', [
                    'reservation' => $reservation,
                    'cancel_reason' => $reason,
                ], function ($message) use ($adminEmail, $adminName, $appName) {
                    if ($adminName !== '') {
                        $message->to($adminEmail, $adminName);
                    } else {
                        $message->to($adminEmail);
                    }
                    $message->subject("予約がキャンセルされました");
                });
            } else {
                Log::warning('[ReservationCancel] MAIL_ADMIN_ADDRESS is invalid. Admin mail skipped.', [
                    'reservation_id' => $reservation->id ?? null,
                    'adminEmail' => $adminEmail,
                    'adminName'  => $adminName,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[ReservationCancel] Mail send failed', [
                'reservation_id' => $reservation->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'キャンセルが完了しました。',
        ], 200);
    }

    /**
     * token(平文) → cancel_token_hash(sha256) で予約検索
     */
    private function findByToken(string $token): ?Reservation
    {
        // cancel_token_hash カラムがなければ、この機能自体が未対応
        if (!Schema::hasColumn('reservations', 'cancel_token_hash')) {
            return null;
        }

        $hash = hash('sha256', $token);

        return Reservation::with('service')
            ->where('cancel_token_hash', $hash)
            ->first();
    }

    /**
     * Next へ返す表示用データ
     */
    private function toSummary(Reservation $r): array
    {
        $tz = config('app.timezone', 'Asia/Tokyo');

        // date が string / Carbon どちらでも安全に
        $dateStr = null;
        if ($r->date instanceof \DateTimeInterface) {
            $dateStr = Carbon::instance($r->date)->toDateString();
        } else {
            $raw = (string) $r->date;
            $dateStr = preg_match('/^\d{4}-\d{2}-\d{2}/', $raw) ? substr($raw, 0, 10) : $raw;
        }

        return [
            'id' => $r->id,
            'name' => (string)($r->name ?? ''),
            'date' => $dateStr,
            'start_time' => $this->toHHmm($r->getRawOriginal('start_time')),
            'end_time' => $this->toHHmm($r->getRawOriginal('end_time')),
            'notes' => $r->notes ?? null,
            'service_name' => optional($r->service)->name ?? null,
            'status' => $r->status ?? null,
        ];
    }

    private function toHHmm($value): ?string
    {
        if ($value === null || $value === '') return null;

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        $str = (string) $value;

        if (preg_match('/^\d{2}:\d{2}$/', $str)) return $str;
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $str)) return substr($str, 0, 5);
        if (preg_match('/(\d{2}:\d{2})/', $str, $m)) return $m[1];

        return null;
    }
}
