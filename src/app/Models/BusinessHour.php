<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 営業時間設定モデル（週単位＋月単位対応版）
 *
 * 各月・週・曜日ごとに「開店時間」「閉店時間」「休業日フラグ」を管理します。
 *
 * 例：
 *  ┌───────────────┬──────┬───────┬──────────────┬───────────┐
 *  │ 年    │ 月  │ 週   │ 曜日   │ 営業時間      │ 休業日     │
 *  ├───────────────┼──────┼───────┼──────────────┼───────────┤
 *  │ 2025 │ 10  │ 1    │ 月     │ 09:00〜17:00 │ false      │
 *  │ 2025 │ 10  │ 1    │ 火     │ 09:00〜17:00 │ false      │
 *  │ 2025 │ 10  │ 1    │ 水     │ NULL          │ true       │
 *  └───────────────┴──────┴───────┴──────────────┴───────────┘
 */
class BusinessHour extends Model
{
    use HasFactory;

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'year',           // 年
        'month',          // 月
        'week_of_month',  // 第何週
        'day_of_week',    // 曜日（例: "月", "火"）
        'open_time',      // 開店時間
        'close_time',     // 閉店時間
        'is_closed',      // 休業日フラグ
    ];

    /**
     * 型キャスト設定
     */
    protected $casts = [
        'is_closed' => 'boolean',
    ];

    /**
     * 曜日の並び順を定義
     */
    public static function orderedDays(): array
    {
        return ['月', '火', '水', '木', '金', '土', '日'];
    }

    /**
     * 曜日の日本語→英語マッピングを返す（必要に応じて使用）
     */
    public static function daysOfWeek(): array
    {
        return [
            '日' => 'Sunday',
            '月' => 'Monday',
            '火' => 'Tuesday',
            '水' => 'Wednesday',
            '木' => 'Thursday',
            '金' => 'Friday',
            '土' => 'Saturday',
        ];
    }

    /**
     * 特定の年月・週番号の営業時間を取得
     */
    public static function forWeek(int $year, int $month, int $week): \Illuminate\Support\Collection
    {
        return self::where('year', $year)
            ->where('month', $month)
            ->where('week_of_month', $week)
            ->orderByRaw("FIELD(day_of_week, '月','火','水','木','金','土','日')")
            ->get();
    }

    /**
     * デフォルトの営業時間を返す（初期登録用）
     */
    public static function defaultHours(): array
    {
        return [
            ['day_of_week' => '月', 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => '火', 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => '水', 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => '木', 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => '金', 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => '土', 'open_time' => '10:00', 'close_time' => '15:00', 'is_closed' => false],
            ['day_of_week' => '日', 'open_time' => null, 'close_time' => null, 'is_closed' => true],
        ];
    }

    /**
     * 指定年月の第1〜第5週分を一括生成する
     * すでに存在する場合はスキップ
     */
    public static function seedDefaultForMonth(int $year, int $month): void
    {
        $default = self::defaultHours();

        for ($week = 1; $week <= 5; $week++) {
            foreach ($default as $hour) {
                self::firstOrCreate(
                    [
                        'year' => $year,
                        'month' => $month,
                        'week_of_month' => $week,
                        'day_of_week' => $hour['day_of_week'],
                    ],
                    [
                        'open_time' => $hour['open_time'],
                        'close_time' => $hour['close_time'],
                        'is_closed' => $hour['is_closed'],
                    ]
                );
            }
        }
    }

    /**
     * 指定日付に対応する週番号を計算
     */
    public static function getWeekOfMonth(Carbon $date): int
    {
        $firstDay = $date->copy()->startOfMonth();
        return (int) ceil(($date->day + $firstDay->dayOfWeekIso - 1) / 7);
    }
}
