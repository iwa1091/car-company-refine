<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Schedule extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)
     * ※ migration のカラムに合わせる
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',           // weekly または exception
        'day_of_week',    // 曜日 (0=日, 1=月, ... 6=土)
        'date',           // 特定日 (exceptionの場合)
        'start_time',     // 営業時間開始 (time / "H:i" or "H:i:s" 想定)
        'end_time',       // 営業時間終了 (time / "H:i" or "H:i:s" 想定)
        'effective_from', // 適用開始日
        'effective_to',   // 適用終了日（null可）
    ];

    /**
     * キャスト設定
     * - date / effective_* は date でOK
     * - start_time / end_time は DB の time 型を想定し、datetime キャストしない
     *   （"09:00" を datetime として解釈しようとして壊れるのを防ぐ）
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_of_week'    => 'integer',
        'date'           => 'date',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /* =========================================================
     * Scopes
     * ========================================================= */

    /**
     * 指定日が有効期間内の weekly スケジュールを取得
     */
    public function scopeWeekly(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('type', 'weekly')
            ->where('effective_from', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * 指定日の exception スケジュールを取得
     */
    public function scopeException(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('type', 'exception')
            ->whereDate('date', $date->format('Y-m-d'));
    }
}
