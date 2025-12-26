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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            // --- スケジュールタイプ ---
            // 'weekly' (曜日ごとの繰り返し) または 'exception' (特定日の例外)
            $table->enum('type', ['weekly', 'exception'])->default('weekly'); 
            
            // --- 曜日ごとの設定 (type = 'weekly' の場合に使用) ---
            // 0: 日曜日, 1: 月曜日, ..., 6: 土曜日
            $table->unsignedTinyInteger('day_of_week')->nullable();
            
            // --- 特定日の設定 (type = 'exception' の場合に使用) ---
            // 臨時休業日や特別営業日など
            $table->date('date')->nullable();
            
            // --- 営業時間 ---
            // nullの場合は終日休業（例外設定では休業日を意味する）
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // 適用開始日と終了日 (スケジュールのバージョン管理)
            $table->date('effective_from')->default(now());
            $table->date('effective_to')->nullable();

            $table->timestamps();

            // 複合インデックス: スケジュールの検索を効率化
            $table->index(['type', 'day_of_week', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
