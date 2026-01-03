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
             * コース（追加）
             * - 既存データ/seed との互換を考慮して nullable
             * - 必須にしたい場合は API バリデーション側で required にするのが安全
             */
            $table->string('course', 100)->nullable();

            /**
             * 予約者情報（ゲスト含む）
             */
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();

            /**
             * 車両情報（追加）
             * - 既存データ/seed との互換を考慮して nullable で追加
             * - 「必須」にしたい場合は API バリデーション側で required にするのが安全
             */
            $table->string('maker', 50)->nullable();
            $table->string('car_model', 100)->nullable();

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
            $table->string('reservation_code')->nullable();

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
