<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Schedule;
use App\Models\Customer;                // 顧客モデル
use App\Models\ScheduledEmail;         // ★ 追加：予約メールスケジュールモデル
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\AdminReservationNoticeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException; // ★ 追加：ユニーク制約エラー等を捕捉

/**
 * 一般ユーザー向けの予約および空き時間チェックAPIを管理するコントローラー
 */
class ReservationController extends Controller
{
    /**
     * 🔍 予約可能時間の確認
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date     = Carbon::parse($request->date);
        $service  = Service::find($request->service_id);
        $duration = $service->duration_minutes ?? 30;

        // 例外スケジュール優先 → なければ通常スケジュール
        $schedule = Schedule::exception($date)->first()
            ?? Schedule::weekly($date)->where('day_of_week', $date->dayOfWeek)->first();

        if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
            return response()->json([
                'available_slots' => [],
                'message'         => '本日は終日休業です。',
            ], 200);
        }

        $openTime  = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time->format('H:i'));
        $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time->format('H:i'));

        // 予約済み時間帯を取得
        $bookedSlots = Reservation::where('date', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get(['start_time', 'end_time'])
            ->map(function ($r) use ($date) {
                return [
                    'start' => Carbon::parse($date->format('Y-m-d') . ' ' . $r->start_time),
                    'end'   => Carbon::parse($date->format('Y-m-d') . ' ' . $r->end_time),
                ];
            })->toArray();

        $availableSlots = [];
        $currentTime    = clone $openTime;

        while ($currentTime->lt($closeTime)) {
            $slotEnd = (clone $currentTime)->addMinutes($duration);
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            $isBooked = collect($bookedSlots)->contains(function ($booked) use ($currentTime, $slotEnd) {
                return (
                    ($currentTime->gte($booked['start']) && $currentTime->lt($booked['end'])) ||
                    ($slotEnd->gt($booked['start']) && $slotEnd->lte($booked['end'])) ||
                    ($currentTime->lt($booked['start']) && $slotEnd->gt($booked['end']))
                );
            });

            if (!$isBooked) {
                $availableSlots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
            }

            $currentTime->addMinutes($duration);
        }

        return response()->json(['available_slots' => $availableSlots], 200);
    }

    /**
     * 📨 予約作成 + メール送信（MailHog対応）
     *
     * ここで：
     *  - 即時メール（予約完了メール／管理者通知）はこれまで通り送信
     *  - 予約日時を基準として、リマインド・サンクスメールを scheduled_emails に登録する
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:20',     // 電話番号も受け取る
            'notes'      => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service  = Service::find($request->service_id);
        $duration = $service->duration_minutes ?? 30;

        $proposedStart = Carbon::parse($request->date . ' ' . $request->start_time);
        $proposedEnd   = (clone $proposedStart)->addMinutes($duration);

        // 🔁 アプリレベルの重複予約チェック
        $isOverlapping = Reservation::where('date', $request->date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($proposedStart, $proposedEnd) {
                $query->where('start_time', '<', $proposedEnd->format('H:i:s'))
                      ->where('end_time', '>', $proposedStart->format('H:i:s'));
            })
            ->exists();

        if ($isOverlapping) {
            return response()->json([
                'message' => '選択された時間枠は既に予約済みです。',
            ], 409);
        }

        // 🔹 ログインユーザー（いれば）
        $user = $request->user();

        // 🔹 顧客情報のベース（User 優先・いなければリクエストから）
        $baseName  = $user ? $user->name  : $request->name;
        $baseEmail = $user ? $user->email : $request->email;
        $basePhone = $user ? $user->phone : $request->phone;

        // 🔹 Customer をメールアドレスで作成 / 更新
        $customer = null;
        if ($baseEmail) {
            $customer = Customer::updateOrCreate(
                ['email' => $baseEmail],
                [
                    'name'  => $baseName,
                    'phone' => $basePhone,
                ]
            );
        }

        // 💾 データベース登録（customer_id / phone を追加）
        try {
            $reservation = Reservation::create([
                'user_id'         => $user?->id,
                'customer_id'     => $customer?->id,                  // 顧客紐づけ
                'service_id'      => $request->service_id,
                'name'            => $baseName,
                'email'           => $baseEmail,
                'phone'           => $basePhone,
                'date'            => $request->date,
                'start_time'      => $proposedStart->format('H:i:s'),
                'end_time'        => $proposedEnd->format('H:i:s'),
                'status'          => 'confirmed',
                'notes'           => $request->notes,
                'reservation_code'=> strtoupper(uniqid('RSV')),
            ]);
        } catch (QueryException $e) {
            // ★ DB ユニーク制約（例: duplicate entry）に引っかかった場合の最終防波堤
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                // すでに同じスロットが DB 上で埋まっている
                return response()->json([
                    'message' => '選択された時間枠は既に他の予約で埋まっています。（DB制約）',
                ], 409);
            }

            Log::error('[予約登録エラー] ' . $e->getMessage(), [
                'date'       => $request->date,
                'start_time' => $request->start_time,
                'service_id' => $request->service_id,
            ]);

            return response()->json([
                'message' => '予約処理中にエラーが発生しました。',
            ], 500);
        }

        $reservation->load('service');

        // 🔹 顧客統計のリフレッシュ
        if ($customer) {
            $customer->recalculateStats();
        }

        // 🔔 リマインド & サンクスメールのスケジュール登録
        try {
            $this->scheduleReservationEmails($reservation, $proposedStart);
        } catch (\Throwable $e) {
            // スケジュール登録に失敗しても、予約自体は成功扱いとし、ログに残す
            Log::error('[予約メールスケジュール登録エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
            ]);
        }

        // ✉️ 即時メール送信処理（DB登録成功後のみ）
        try {
            // 顧客宛
            Mail::to($reservation->email)->send(new ReservationConfirmedMail($reservation));

            // 管理者宛
            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationNoticeMail($reservation));

            if (count(Mail::failures()) > 0) {
                Log::warning('[メール送信失敗] 一部メール送信に失敗しました。', [
                    'reservation_id' => $reservation->id,
                    'failures'       => Mail::failures(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[メール送信エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
                'email'          => $reservation->email ?? null,
            ]);
            // ※ メール失敗だけでは 500 は返さず、予約自体は成功扱い
        }

        return response()->json([
            'message'     => '予約が完了しました（確認メールを送信しました）。',
            'reservation' => $reservation,
        ], 201);
    }

    /**
     * 📋 管理者向け一覧API
     */
    public function index()
    {
        $reservations = Reservation::with('service')
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'name'         => $r->name,
                'service_name' => $r->service->name ?? '未設定',
                'date'         => $r->date,
                'start_time'   => $r->start_time,
                'status'       => $r->status ?? '予約中',
            ]);

        return response()->json($reservations);
    }

    /**
     * ❌ 管理者用：予約削除API
     */
    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }

        $reservation->delete();

        return response()->json(['message' => '削除しました。'], 200);
    }

    /* =====================================================
     * 🔔 予約メールスケジュール登録関連（private メソッド）
     * ===================================================== */

    /**
     * 予約日時を基準に、リマインド／サンクスメールを scheduled_emails に登録する
     *
     * - リマインド：2日前 + 前日
     * - サンクス：3日後
     * - 再来店促進：1か月後
     */
    protected function scheduleReservationEmails(Reservation $reservation, Carbon $startDateTime): void
    {
        $email  = $reservation->email;
        $userId = $reservation->user_id;

        // 2日前リマインド
        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_reminder_2days',
            $startDateTime->copy()->subDays(2)
        );

        // 前日リマインド
        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_reminder_1day',
            $startDateTime->copy()->subDay()
        );

        // 3日後サンクス
        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_thanks_3days',
            $startDateTime->copy()->addDays(3)
        );

        // 1か月後再来店促進
        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_thanks_1month',
            $startDateTime->copy()->addMonth()
        );
    }

    /**
     * scheduled_emails テーブルへ1件登録する
     *
     * ※ send_at がすでに現在時刻を過ぎている場合はスキップ（デバッグ時の暴走防止）
     */
    protected function createScheduleEntry(
        Reservation $reservation,
        ?int $userId,
        string $email,
        string $type,
        Carbon $sendAt
    ): void {
        // 予約作成タイミングがギリギリのときは、過去になっているスケジュールは作らない
        if ($sendAt->lte(now())) {
            return;
        }

        ScheduledEmail::create([
            'user_id'      => $userId,
            'email'        => $email,
            'type'         => $type,
            'related_type' => Reservation::class,
            'related_id'   => $reservation->id,
            'send_at'      => $sendAt,
            'status'       => 'pending',
        ]);
    }
}
