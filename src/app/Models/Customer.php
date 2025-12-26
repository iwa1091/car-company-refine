<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'total_reservations',
        'total_purchases',
        'total_spent',
        'last_reservation_at',
        'last_purchase_at',
        'memo',
    ];

    /**
     * 顧客の予約履歴（1対多）
     * reservations テーブルの customer_id を前提
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * 顧客の購入履歴（1対多）
     * orders テーブルの customer_id を前提
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 顧客の総利用回数・購入回数・総支出・最終利用日を再計算する
     * ReservationController や 購入処理 から呼び出して使用する想定
     */
    public function recalculateStats(): void
    {
        // 🔹 予約回数・最終予約日（ここはこれまでどおり全予約を対象）
        $reservationQuery = $this->reservations();

        $this->total_reservations   = $reservationQuery->count();
        $this->last_reservation_at  = $reservationQuery->max('date');

        // 🔹 購入回数・総支出・最終購入日は「支払い完了（paid）の注文のみ」集計
        $paidOrders = $this->orders()->where('payment_status', 'paid');

        $this->total_purchases  = $paidOrders->count();
        $this->total_spent      = $paidOrders->sum('amount');
        $this->last_purchase_at = $paidOrders->max('ordered_at');

        // 変更を保存
        $this->save();
    }
}
