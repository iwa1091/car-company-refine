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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // サービス名
            $table->string('name')->unique();
            
            // サービスの説明
            $table->text('description')->nullable();
            
            // 所要時間（分単位）: duration_minutes カラムを追加
            $table->unsignedSmallInteger('duration_minutes');  // 所要時間（分単位）
            
            // 価格（円単位）
            $table->decimal('price', 8, 0)->unsigned();

            // ✅ 追加：価格の表示用テキスト（例：10,000円 / 要相談 / キャンペーン中 など）
            $table->string('price_text')->nullable();
            
            // 表示順序
            $table->unsignedSmallInteger('sort_order')->default(0);

            // サービスが有効かどうか
            $table->boolean('is_active')->default(true);

            // 画像ファイルのパス
            $table->string('image')->nullable();

            // サービス特徴（JSON配列）
            $table->json('features')->nullable();

            // カテゴリID（外部キー）
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->onDelete('set null');

            // 人気フラグ
            $table->boolean('is_popular')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
