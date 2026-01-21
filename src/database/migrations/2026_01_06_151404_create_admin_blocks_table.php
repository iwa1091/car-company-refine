<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_blocks', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // ✅ 固定レーン（予約は常に枠1なので、ブロックは主に 2 or 3）
            $table->unsignedTinyInteger('lane')->default(2); // 1,2,3

            // ✅ 表示内容（要件：名前/車種/コース/メニュー）
            $table->string('name')->nullable();
            $table->string('maker', 50)->nullable();
            $table->string('car_model', 100)->nullable();
            $table->string('course', 100)->nullable();
            $table->string('menu', 255)->nullable(); // 管理者ブロック用の「メニュー名」

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['date', 'lane']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_blocks');
    }
};