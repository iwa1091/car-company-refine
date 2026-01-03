<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'service_id',

        // ✅ 追加（コース）
        'course',

        'name',
        'email',
        'phone',

        // ✅ 追加（車両情報）
        'maker',
        'car_model',

        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'reservation_code',

        // ※テーブルに存在する前提（存在しない環境でも代入しなければ問題になりません）
        'cancel_token_hash',
        'cancelled_at',
        'cancel_reason',
    ];

    /**
     * 🔹 User（顧客）とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔹 Service（メニュー）とのリレーション
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * 🔹 Customer（顧客マスタ）とのリレーション（customer_id を使っているので追加）
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 🔹 日付・時間系のキャスト設定
     *
     * ✅重要：
     * start_time / end_time を datetime キャストすると、
     * Carbon → ISO(Z) になったり、タイムゾーン計算で +9h ずれる原因になります。
     * time型は「文字列」として扱い、表示はアクセサで HH:mm に統一します。
     */
    protected $casts = [
        'date'         => 'date',
        'cancelled_at' => 'datetime',
    ];

    /* =====================================================
     * アクセサ：time の正規化（ここがズレ対策の本体）
     * ===================================================== */

    /**
     * start_time を常に "HH:mm" で返す
     */
    public function getStartTimeAttribute($value): ?string
    {
        return $this->formatTimeToHHmm($value);
    }

    /**
     * end_time を常に "HH:mm" で返す
     */
    public function getEndTimeAttribute($value): ?string
    {
        return $this->formatTimeToHHmm($value);
    }

    /**
     * 🔹 アクセサ：表示用フォーマット
     */
    public function getFormattedDateAttribute(): string
    {
        // date は cast 済みの場合があるため Carbon 経由で安全に
        return Carbon::parse($this->date)->format('Y年m月d日');
    }

    public function getFormattedTimeAttribute(): string
    {
        // start_time はアクセサで "HH:mm" になっている
        return $this->start_time ?: '';
    }

    /**
     * 🔹 状態ラベル
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => '確定',
            'pending'   => '保留',
            'cancelled' => 'キャンセル',
            'completed' => '完了',
            default     => '不明',
        };
    }

    /* =====================================================
     * 内部ユーティリティ
     * ===================================================== */

    /**
     * time/datetime/ISO などを "HH:mm" に正規化して返す
     *
     * - DB time: "09:00:00" → "09:00"
     * - 文字列: "09:00" → "09:00"
     * - ISO: "2025-12-29T09:00:00.000000Z" → "09:00"（※タイムゾーン変換しない）
     * - Carbon/DateTime: → "H:i"
     */
    private function formatTimeToHHmm($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // DateTime/Carbon の場合（※変換はせず、そのまま時刻部分）
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        $str = trim((string) $value);

        // "HH:MM" / "HH:MM:SS"
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $str)) {
            return substr($str, 0, 5);
        }

        // ISO / "YYYY-MM-DD HH:MM:SS" などから "HH:MM" を抜く（タイムゾーン変換しない）
        if (preg_match('/\b(\d{2}:\d{2})(?::\d{2})?\b/', $str, $m)) {
            return $m[1];
        }

        // どうしても拾えない場合は null（UI側で空扱いにできる）
        return null;
    }
}
