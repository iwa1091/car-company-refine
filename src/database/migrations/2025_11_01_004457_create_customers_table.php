<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->integer('total_reservations')->default(0);
            $table->integer('total_purchases')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('last_reservation_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // ✅ 予約テーブルに外部キー追加（orders は後で作成されるためここでは追加しない）
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'customer_id')) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('customers')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });

        Schema::dropIfExists('customers');
    }
};
