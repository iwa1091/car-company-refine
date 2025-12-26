<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();

            // 任意：ユーザー（ログインユーザー）がいる場合に紐づけ
            $table->unsignedBigInteger('user_id')->nullable()->comment('関連ユーザーID（任意）');

            // 送信先メールアドレス
            $table->string('email')->comment('送信先メールアドレス');

            // メール種別（例：reservation_reminder_2days など）
            $table->string('type')->comment('メール種別');

            // 予約や注文などの関連モデル（Reservation / Order など）に紐づける
            $table->nullableMorphs('related'); // related_type, related_id（nullable + index）

            // 送信予定日時 / 実際に送信した日時
            $table->timestamp('send_at')->comment('送信予定日時');
            $table->timestamp('sent_at')->nullable()->comment('実際の送信日時');

            // ステータス
            // pending: 送信待ち / sent: 送信済み / failed: 送信失敗
            $table->string('status')->default('pending')->comment('pending / sent / failed');
            $table->text('error_message')->nullable()->comment('エラー内容');

            $table->timestamps();

            // よく使うであろう組み合わせにインデックス
            $table->index(['status', 'send_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_emails');
    }
};
