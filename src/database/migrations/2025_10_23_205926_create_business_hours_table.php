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
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();

            // 🔹 年（例: 2025）
            $table->integer('year')->comment('対象年');

            // 🔹 月（例: 10）
            $table->integer('month')->comment('対象月');

            // 🔹 第何週（1〜5週を想定）
            $table->integer('week_of_month')->comment('月内の週番号（第1〜第5週）');

            // 🔹 曜日（例: 月, 火, 水, 木, 金, 土, 日）
            $table->string('day_of_week', 10)->comment('曜日');

            // 🔹 開店・閉店時間（休業日の場合はNULL）
            $table->time('open_time')->nullable()->comment('開店時間');
            $table->time('close_time')->nullable()->comment('閉店時間');

            // 🔹 休業フラグ
            $table->boolean('is_closed')->default(false)->comment('休業日かどうか');

            $table->timestamps();

            // 🔹 同じ年月・週・曜日の重複登録を防ぐ
            $table->unique(['year', 'month', 'week_of_month', 'day_of_week'], 'unique_weekly_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
