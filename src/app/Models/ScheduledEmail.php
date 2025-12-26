<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'type',
        'related_type',
        'related_id',
        'send_at',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * 予約や注文など、関連レコードへのポリモーフィックリレーション
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * scope: 送信待ち
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
