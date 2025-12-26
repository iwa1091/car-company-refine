<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            /**
             * ユーザー（ログインユーザーの場合は紐づく）
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            /**
             * サービス（メニュー）
             */
            $table->foreignId('service_id')
                ->constrained()
                ->onDelete('cascade');

            /**
             * 予約者情報（ゲスト含む）
             */
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();   // ← 追加

            /**
             * 日付・時間
             */
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            /**
             * 状態
             */
            $table->string('status')
                ->default('pending')
                ->comment('pending, confirmed, cancelled, completed');

            /**
             * 備考
             */
            $table->text('notes')->nullable();

            /**
             * マイページ紐づけ用の予約コード（RSVxxxxxx）
             */
            $table->string('reservation_code')->nullable(); // ← 追加

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
