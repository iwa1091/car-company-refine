<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // メールキャンセル用トークン（DBにはハッシュのみ保存）
            $table->string('cancel_token_hash', 64)
                ->nullable()
                ->unique()
                ->after('reservation_code');

            // キャンセル日時
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('cancel_token_hash');

            // キャンセル理由（ユーザー入力）
            $table->text('cancel_reason')
                ->nullable()
                ->after('cancelled_at');

            // 実運用でよく使う検索を軽くする（任意だが推奨）
            $table->index(['date', 'start_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['date', 'start_time']);
            $table->dropIndex(['status']);
            $table->dropUnique(['cancel_token_hash']);

            $table->dropColumn([
                'cancel_token_hash',
                'cancelled_at',
                'cancel_reason',
            ]);
        });
    }
};
