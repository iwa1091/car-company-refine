<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminReservationNoticeMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ScheduledEmail;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * 🗓 指定年月の営業カレンダー（1日ごとの open/close/休業）
     * GET /api/reservations/month-schedule?year=2025&month=12
     * return: { days: [{date,is_closed,open_time,close_time}, ...], closed_dates:[...](互換) }
     */
    public function monthSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $year  = (int) $request->year;
        $month = (int) $request->month;

        $this->ensureBusinessHoursSeeded($year, $month);

        $tz = config('app.timezone', 'Asia/Tokyo');

        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        $result = [];
        $closedDates = []; // ✅ 追加（互換用）

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $bh = $this->resolveBusinessHourForDate($date);

            if (!$bh || (bool) $bh->is_closed || empty($bh->open_time) || empty($bh->close_time)) {
                $result[] = [
                    'date'       => $date->toDateString(),
                    'is_closed'  => true,
                    'open_time'  => null,
                    'close_time' => null,
                ];
                $closedDates[] = $date->toDateString(); // ✅ 追加
                continue;
            }

            $openHHmm  = $this->toHHmm($bh->open_time);
            $closeHHmm = $this->toHHmm($bh->close_time);

            // フォーマットが壊れている場合は休業扱い
            if (!$openHHmm || !$closeHHmm) {
                $result[] = [
                    'date'       => $date->toDateString(),
                    'is_closed'  => true,
                    'open_time'  => null,
                    'close_time' => null,
                ];
                $closedDates[] = $date->toDateString(); // ✅ 追加
                continue;
            }

            $result[] = [
                'date'       => $date->toDateString(),
                'is_closed'  => false,
                'open_time'  => $openHHmm,
                'close_time' => $closeHHmm,
            ];
        }

        return response()->json([
            'days'         => $result,
            'closed_dates' => $closedDates, // ✅ 追加（互換用）
        ], 200);
    }

    /**
     * 🗓 指定年月の休業日一覧
     * GET /api/business-hours/closed-dates?year=2025&month=12
     * return: { year, month, closed_dates: ["YYYY-MM-DD", ...] }
     */
    public function closedDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $year  = (int) $request->year;
        $month = (int) $request->month;

        $this->ensureBusinessHoursSeeded($year, $month);

        $tz = config('app.timezone', 'Asia/Tokyo');

        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        $closed = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $bh = $this->resolveBusinessHourForDate($date);

            $isClosed = (!$bh)
                || (bool) $bh->is_closed
                || empty($bh->open_time)
                || empty($bh->close_time);

            if ($isClosed) {
                $closed[] = $date->toDateString();
            }
        }

        return response()->json([
            'year'         => $year,
            'month'        => $month,
            'closed_dates' => $closed,
        ], 200);
    }

    /**
     * 🔍 予約可能時間の確認（business_hours 基準）
     * GET /api/reservations/check?date=YYYY-MM-DD&service_id=ID
     *
     * return:
     * 200 { available_slots: [{start:"HH:MM", end:"HH:MM"}], business_hour:{open_time,close_time} }
     * 422 { message, errors }
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'service_id' => ['required', 'exists:services,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tz = config('app.timezone', 'Asia/Tokyo');

        $date     = Carbon::createFromFormat('Y-m-d', $request->date, $tz)->startOfDay();
        $service  = Service::find($request->service_id);
        $duration = (int) ($service->duration_minutes ?? 30);

        $this->ensureBusinessHoursSeeded((int)$date->year, (int)$date->month);
        $bh = $this->resolveBusinessHourForDate($date);

        if (!$bh || $bh->is_closed || !$bh->open_time || !$bh->close_time) {
            return response()->json([
                'available_slots' => [],
                'availableSlots'  => [], // ✅ 追加（互換）
                'message'         => '本日は終日休業です。',
            ], 200);
        }

        $openHHmm  = $this->toHHmm($bh->open_time);
        $closeHHmm = $this->toHHmm($bh->close_time);

        if (!$openHHmm || !$closeHHmm) {
            return response()->json([
                'available_slots' => [],
                'availableSlots'  => [], // ✅ 追加（互換）
                'message'         => '本日は終日休業です。',
            ], 200);
        }

        $openTime  = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$openHHmm, $tz);
        $closeTime = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$closeHHmm, $tz);

        if ($closeTime->lte($openTime)) {
            return response()->json([
                'available_slots' => [],
                'availableSlots'  => [], // ✅ 追加（互換）
                'message'         => '営業時間設定が不正です。',
            ], 200);
        }

        /**
         * 予約済み枠（DBの生値(time)で扱う：casts(datetime)でもズレない）
         */
        $bookedSlots = Reservation::where('date', $date->toDateString())
            ->where('status', 'confirmed')
            ->get(['start_time', 'end_time'])
            ->map(function ($r) use ($date, $tz) {
                $startRaw = $r->getRawOriginal('start_time');
                $endRaw   = $r->getRawOriginal('end_time');

                $startHHmm = $this->toHHmm($startRaw);
                $endHHmm   = $this->toHHmm($endRaw);

                return [
                    'start' => $startHHmm ? Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$startHHmm, $tz) : null,
                    'end'   => $endHHmm   ? Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$endHHmm,   $tz) : null,
                ];
            })
            ->filter(fn ($x) => $x['start'] && $x['end'])
            ->values()
            ->toArray();

        $availableSlots = [];

        // 枠の刻みは 15分固定
        $stepMinutes = 15;
        $currentTime = $openTime->copy();

        // ✅ 予約受付は「開始1時間前まで」
        $now = Carbon::now($tz)->second(0);
        $minStart = $now->copy()->addHour();
        $mod = ((int)$minStart->minute) % $stepMinutes;
        if ($mod !== 0) {
            $minStart->addMinutes($stepMinutes - $mod);
        }

        while ($currentTime->lt($closeTime)) {
            $slotEnd = $currentTime->copy()->addMinutes($duration);
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            if ($currentTime->lt($minStart)) {
                $currentTime->addMinutes($stepMinutes);
                continue;
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

            $currentTime->addMinutes($stepMinutes);
        }

        $businessHour = [
            'open_time'  => $openHHmm,
            'close_time' => $closeHHmm,
        ];

        return response()->json([
            'available_slots' => $availableSlots,
            'availableSlots'  => $availableSlots, // ✅ 追加（互換）
            'business_hour'   => $businessHour,
            'businessHour'    => $businessHour,    // ✅ 追加（互換）
        ], 200);
    }

    /**
     * 📨 予約作成
     * POST /api/reservations
     */
    public function store(Request $request)
    {
        // ✅ 追加：日本語メッセージを揃える（フロント firstError() 用）
        $rules = [
            'service_id'  => ['required', 'exists:services,id'],
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],

            // ✅ フロントは必須なので required に寄せる（不一致防止）
            'phone'       => ['required', 'string', 'max:20'],

            // ✅ 追加（必須：車両情報）
            'maker'       => ['required', 'string', 'max:50'],
            'car_model'   => ['required', 'string', 'max:100'],

            // ✅ 追加（コース：任意）
            'course'      => ['nullable', 'string', 'max:255'],

            'date'        => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i'], // フロント互換のため維持（保存はサーバー計算を採用）
            'notes'       => ['nullable', 'string', 'max:500'],
        ];

        $messages = [
            'service_id.required' => 'メニューを選択してください。',
            'service_id.exists'   => '選択されたメニューが存在しません。',

            'name.required'       => 'お名前は必須です。',
            'name.max'            => 'お名前は255文字以内で入力してください。',

            'email.required'      => 'メールアドレスは必須です。',
            'email.email'         => 'メールアドレスの形式が正しくありません。',
            'email.max'           => 'メールアドレスは255文字以内で入力してください。',

            'phone.required'      => '電話番号は必須です。',
            'phone.max'           => '電話番号は20文字以内で入力してください。',

            'maker.required'      => 'メーカーは必須です。',
            'maker.max'           => 'メーカーは50文字以内で入力してください。',

            'car_model.required'  => '車種は必須です。',
            'car_model.max'       => '車種は100文字以内で入力してください。',

            'course.max'          => 'コースは255文字以内で入力してください。',

            'date.required'       => '希望日は必須です。',
            'date.date_format'    => '希望日の形式が不正です。',
            'date.after_or_equal' => '過去の日付は選択できません。',

            'start_time.required'    => '時間を選択してください。',
            'start_time.date_format' => '時間の形式が不正です。',

            'end_time.required'    => '終了時刻が不正です。',
            'end_time.date_format' => '終了時刻の形式が不正です。',

            'notes.max'           => '備考は500文字以内で入力してください。',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tz = config('app.timezone', 'Asia/Tokyo');

        $date = Carbon::createFromFormat('Y-m-d', $request->date, $tz)->startOfDay();

        $this->ensureBusinessHoursSeeded((int)$date->year, (int)$date->month);
        $bh = $this->resolveBusinessHourForDate($date);

        if (!$bh || $bh->is_closed || !$bh->open_time || !$bh->close_time) {
            return response()->json([
                'message' => '本日は終日休業です。',
            ], 422);
        }

        $openHHmm  = $this->toHHmm($bh->open_time);
        $closeHHmm = $this->toHHmm($bh->close_time);

        if (!$openHHmm || !$closeHHmm) {
            return response()->json([
                'message' => '営業時間設定が不正です。',
            ], 422);
        }

        $openTime  = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$openHHmm, $tz);
        $closeTime = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$closeHHmm, $tz);

        $startDt = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.$request->start_time, $tz);

        // ✅ 15分刻みチェック（サーバー側の最終防衛線）
        if (((int)$startDt->minute) % 15 !== 0) {
            return response()->json(['message' => '開始時刻は15分刻みで選択してください。'], 422);
        }

        // ✅ end_time はサーバー計算に統一（フロントは変更不要）
        $service  = Service::find($request->service_id);
        $duration = (int) ($service->duration_minutes ?? 30);
        $endDt    = $startDt->copy()->addMinutes($duration);

        if ($endDt->lte($startDt)) {
            return response()->json(['message' => '終了時刻が不正です。'], 422);
        }

        // ✅ 予約受付は「開始1時間前まで」（checkAvailability と同じ丸め方で強制）
        $stepMinutes = 15;
        $now = Carbon::now($tz)->second(0);
        $minStart = $now->copy()->addHour();
        $mod = ((int)$minStart->minute) % $stepMinutes;
        if ($mod !== 0) {
            $minStart->addMinutes($stepMinutes - $mod);
        }

        if ($startDt->lt($minStart)) {
            return response()->json(['message' => '予約は開始1時間前まで受け付けています。'], 422);
        }

        // 営業時間内チェック（開始/終了が営業時間内）
        if ($startDt->lt($openTime) || $endDt->gt($closeTime)) {
            return response()->json(['message' => '時間は営業時間内で選択してください。'], 422);
        }

        // DB保存用（time型想定）に揃える
        $startForDb = $startDt->format('H:i:s');
        $endForDb   = $endDt->format('H:i:s');

        // 顧客（emailキーで統一）
        $customer = Customer::updateOrCreate(
            ['email' => $request->email],
            [
                'name'  => $request->name,
                'phone' => $request->phone,
            ]
        );

        try {
            $reservation = DB::transaction(function () use ($request, $date, $startForDb, $endForDb, $customer) {
                // 重複予約防止（同日で時間帯が重なる confirmed をロックして確認）
                $conflict = Reservation::where('date', $date->toDateString())
                    ->where('status', 'confirmed')
                    ->where('start_time', '<', $endForDb)
                    ->where('end_time', '>', $startForDb)
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException('その時間帯はすでに予約があります。');
                }

                $reservation = Reservation::create([
                    'service_id'   => $request->service_id,
                    'customer_id'  => $customer->id,
                    'name'         => $request->name,
                    'email'        => $request->email,
                    'phone'        => $request->phone,

                    // ✅ 追加（車両情報）
                    'maker'        => $request->maker,
                    'car_model'    => $request->car_model,

                    // ✅ 追加（コース）
                    'course'       => $request->course,

                    'date'         => $date->toDateString(),
                    'start_time'   => $startForDb,
                    'end_time'     => $endForDb,
                    'status'       => 'confirmed',
                    'notes'        => $request->notes,
                    'reservation_code' => strtoupper(uniqid('RSV')),
                ]);

                // cancel_token_hash カラムがある場合のみ生成（他ファイル不一致の保険）
                if (Schema::hasColumn('reservations', 'cancel_token_hash')) {
                    $plain = Str::random(64);
                    $reservation->cancel_token_hash = hash('sha256', $plain);
                    $reservation->save();
                    // 平文トークンはメールで使う前提ならここで保持（返却しない）
                    $reservation->setAttribute('__plain_cancel_token', $plain);
                }

                return $reservation;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (QueryException $e) {
            Log::warning('[Reservation store] QueryException', ['error' => $e->getMessage()]);
            return response()->json(['message' => '予約の登録に失敗しました。'], 500);
        } catch (\Throwable $e) {
            Log::error('[Reservation store] Unexpected error', ['error' => $e->getMessage()]);
            return response()->json(['message' => '予約の登録に失敗しました。'], 500);
        }

        // cancelUrl を生成（トークンがあれば）
        $cancelUrl = null;
        try {
            $plainToken = $reservation->getAttribute('__plain_cancel_token');
            if ($plainToken) {
                $cancelUrl = route('reservations.cancel.show', ['token' => $plainToken], true);
            }
        } catch (\Throwable $e) {
            Log::warning('Cancel URL build failed: ' . $e->getMessage());
        }

        // ✅ メール送信（1回だけ / 必ず try 内）
        try {
            // ✅ config を優先（本番の config:cache 運用で事故りにくくする）
            $adminEmail = config('mail.admin_address');
            $adminName  = config('mail.admin_name', 'Admin');

            // 念のためフォールバック（環境によっては config 未反映のケースを救う）
            if (!$adminEmail) {
                $adminEmail = env('MAIL_ADMIN_ADDRESS');
            }
            if (!$adminName) {
                $adminName = env('MAIL_ADMIN_NAME', 'Admin');
            }

            // ここで型と空白を正規化（null/配列混入を防ぐ）
            $adminEmail = is_string($adminEmail) ? trim($adminEmail) : '';
            $adminName  = is_string($adminName) ? trim($adminName) : 'Admin';

            // ユーザー宛
            Mail::to($reservation->email)->send(new ReservationConfirmedMail($reservation, $cancelUrl));

            // 管理者宛：✅ 連想配列を使わず「必ず文字列アドレス」で送る
            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($adminEmail, ($adminName !== '' ? $adminName : null))
                    ->send(new AdminReservationNoticeMail($reservation, $cancelUrl));
            } else {
                Log::warning('[Reservation store] MAIL_ADMIN_ADDRESS is invalid. Admin email skipped.', [
                    'adminEmail' => $adminEmail,
                    'adminName'  => $adminName,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Reservation store] Mail send failed', ['error' => $e->getMessage()]);
        }

        // スケジュールメール作成（テーブルがある場合のみ）
        try {
            if (Schema::hasTable('scheduled_emails')) {
                $this->scheduleEmailsIfNeeded($reservation, $tz);
            }
        } catch (\Throwable $e) {
            Log::warning('[Reservation store] ScheduledEmail create failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => '予約が完了しました。',
            'reservation' => [
                'id'               => $reservation->id,
                'reservation_code'  => $reservation->reservation_code,
                'date'             => $reservation->date,
                'start_time'       => $this->toHHmm($reservation->getRawOriginal('start_time')),
                'end_time'         => $this->toHHmm($reservation->getRawOriginal('end_time')),
                'status'           => $reservation->status,
                'name'             => $reservation->name,
                'email'            => $reservation->email,
                'phone'            => $reservation->phone,

                // ✅ 追加（車両情報）
                'maker'            => $reservation->maker ?? null,
                'car_model'        => $reservation->car_model ?? null,

                // ✅ 追加（コース）
                'course'           => $reservation->course ?? null,

                'service_id'       => $reservation->service_id,
            ],
        ], 201);
    }

    /**
     * scheduled_emails に登録（例：リマインド等）
     */
    private function scheduleEmailsIfNeeded(Reservation $reservation, string $tz): void
    {
        $startHHmm = $this->toHHmm($reservation->getRawOriginal('start_time'));
        if (!$startHHmm) return;

        // ✅ date が Carbon cast の場合に "YYYY-MM-DD 00:00:00" になり createFromFormat がコケるのを防ぐ
        $dateStr = null;
        if ($reservation->date instanceof \DateTimeInterface) {
            $dateStr = Carbon::instance($reservation->date)->toDateString();
        } else {
            $raw = (string) $reservation->date;
            $dateStr = preg_match('/^\d{4}-\d{2}-\d{2}/', $raw) ? substr($raw, 0, 10) : $raw;
        }

        $startAt = Carbon::createFromFormat('Y-m-d H:i', $dateStr.' '.$startHHmm, $tz);

        $remindAt = $startAt->copy()->subDay()->setTime(9, 0);

        if ($remindAt->isFuture()) {
            ScheduledEmail::create([
                'to_email'    => $reservation->email,
                'type'        => 'reservation_reminder',
                'send_at'     => $remindAt,
                'payload'     => json_encode(['reservation_id' => $reservation->id], JSON_UNESCAPED_UNICODE),
                'status'      => 'pending',
            ]);
        }
    }

    /**
     * business_hours が無ければ当月分を初期投入
     */
    private function ensureBusinessHoursSeeded(int $year, int $month): void
    {
        if (!Schema::hasTable('business_hours')) {
            return;
        }

        $exists = BusinessHour::where('year', $year)->where('month', $month)->exists();
        if (!$exists) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }
    }

    /**
     * 指定日付の business_hours を解決（週×曜日）
     * 無ければ「同月の別週の同曜日」をフォールバック
     */
    private function resolveBusinessHourForDate(Carbon $date): ?BusinessHour
    {
        $year  = (int) $date->year;
        $month = (int) $date->month;
        $week  = BusinessHour::getWeekOfMonth($date);
        $dayJa = $this->dayOfWeekJa($date);

        $bh = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->where('week_of_month', $week)
            ->where('day_of_week', $dayJa)
            ->first();

        if ($bh) return $bh;

        return BusinessHour::where('year', $year)
            ->where('month', $month)
            ->where('day_of_week', $dayJa)
            ->orderBy('week_of_month')
            ->first();
    }

    /**
     * time/datetime/Carbon を "HH:mm" に正規化
     */
    private function toHHmm($value): ?string
    {
        if ($value === null || $value === '') return null;

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        $str = (string) $value;

        if (preg_match('/^\d{2}:\d{2}$/', $str)) {
            return $str;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $str)) {
            return substr($str, 0, 5);
        }

        if (preg_match('/(\d{2}:\d{2})/', $str, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Carbon日付 → 曜日（日本語）
     */
    private function dayOfWeekJa(Carbon $date): string
    {
        $map = ['月', '火', '水', '木', '金', '土', '日'];
        return $map[$date->dayOfWeekIso - 1] ?? '月';
    }
}
