<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // 購入者
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null'); // 商品
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null'); // 顧客統合用（後から紐づけ）

            $table->string('order_number')->unique(); // 注文番号（StripeやUUIDなど）
            $table->decimal('amount', 10, 2); // 金額
            $table->string('currency')->default('JPY');
            $table->string('payment_status')->default('pending'); // pending / paid / failed / refunded
            $table->string('stripe_payment_id')->nullable(); // Stripe決済ID
            $table->string('stripe_session_id')->nullable(); // セッションID（任意）

            $table->string('shipping_name')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_phone')->nullable();

            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
