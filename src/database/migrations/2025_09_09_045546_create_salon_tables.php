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
        // ビフォー・アフター事例
        Schema::create('before_after_cases', function (Blueprint $table) {
            $table->id();
            $table->string('before_url');
            $table->string('after_url');
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });

        // ギャラリー画像
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('title');
            $table->string('category');
            $table->timestamps();
        });

        // お客様の声（レビュー）
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('age');
            $table->integer('rating');
            $table->text('comment');
            $table->string('service');
            $table->string('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('before_after_cases');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('reviews');
    }
};