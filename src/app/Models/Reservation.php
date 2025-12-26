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
        'user_id',          // 顧客 (User)
        'service_id',       // メニュー (Service)
        'name',
        'email',
        'phone',            // ← 追加（必須）
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'reservation_code', // マイページ紐づけ番号
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
     * 🔹 日付・時間系のキャスト設定
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    /**
     * 🔹 アクセサ：表示用フォーマット
     */
    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->date)->format('Y年m月d日');
    }

    public function getFormattedTimeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    /**
     * 🔹 状態ラベル
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => '確定',
            'pending'   => '保留',
            'canceled'  => 'キャンセル',
            default     => '不明',
        };
    }
}
