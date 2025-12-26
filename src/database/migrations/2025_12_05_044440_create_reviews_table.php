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
        Schema::dropIfExists('reviews');
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // お客様名（例: "清水様"）
            $table->string('name');

            // 年代（例: "30代"） - 任意なので nullable
            $table->string('age')->nullable();

            // 評価（★1〜5 を想定）
            $table->unsignedTinyInteger('rating');

            // 口コミ本文
            $table->text('comment');

            // 施術メニュー名（例: "眉毛スタイリング"）- 任意
            $table->string('service')->nullable();

            // 表示用の日付（例: "2024年05月"）- 文字列で管理
            $table->string('date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
