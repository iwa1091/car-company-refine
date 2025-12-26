<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'customer_id',
        'order_number',
        'amount',
        'currency',
        'payment_status',
        'stripe_payment_id',
        'stripe_session_id',
        'shipping_name',
        'shipping_address',
        'shipping_phone',
        'ordered_at',
    ];

    /**
     * 【修正点】ordered_at カラムを Carbon オブジェクトとして自動的にキャストする設定を追加
     * これにより、Bladeテンプレート内で ->format() メソッドが使用可能になる。
     */
    protected $casts = [
        'ordered_at' => 'datetime',
    ];

    /**
     * 顧客（Customer）との関係：1件の注文は1人の顧客に属する
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 購入者（ログインユーザー）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 商品との関係
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}