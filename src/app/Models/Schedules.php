<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)。
     * マイグレーションで定義した全カラムを含めます。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',             // weekly または exception
        'day_of_week',      // 曜日 (0=日, 1=月, ...)
        'date',             // 特定日 (exceptionの場合)
        'start_time',       // 営業時間開始
        'end_time',         // 営業時間終了
        'effective_from',   // 適用開始日
        'effective_to',     // 適用終了日
    ];

    /**
     * モデルの属性のキャスト設定。
     * データベースから取得した際に自動的に型変換を行います。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_of_week' => 'integer',
        'date' => 'date',
        'start_time' => 'datetime', // Carbonインスタンスとして扱えるようにキャスト
        'end_time' => 'datetime',   // Carbonインスタンスとして扱えるようにキャスト
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // スコープの例: 指定日が有効期間内の基本スケジュールを取得
    public function scopeWeekly(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('type', 'weekly')
                     ->where('effective_from', '<=', $date)
                     ->where(function ($q) use ($date) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $date);
                     });
    }

    // スコープの例: 指定日の例外スケジュールを取得
    public function scopeException(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('type', 'exception')
                     ->where('date', $date->format('Y-m-d'));
    }
}
