<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // services テーブルに category カラムが存在しない場合は処理をスキップ
        if (!Schema::hasColumn('services', 'category')) {
            return;
        }

        // 1. 既存の services からカテゴリ名の一覧を取得（重複なし）
        $categories = DB::table('services')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter() // null/空文字を除外
            ->values();

        // 2. categories テーブルに登録
        foreach ($categories as $categoryName) {
            DB::table('categories')->insert([
                'name' => $categoryName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. services の category_id を設定
        $services = DB::table('services')->get();

        foreach ($services as $service) {
            if ($service->category) {
                $category = DB::table('categories')
                    ->where('name', $service->category)
                    ->first();

                if ($category) {
                    DB::table('services')
                        ->where('id', $service->id)
                        ->update(['category_id' => $category->id]);
                }
            }
        }

        // 4. 旧 category カラムを削除
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // services テーブルに category カラムが存在しない場合のみ追加
        if (!Schema::hasColumn('services', 'category')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('category')->nullable()->after('features');
            });

            // category_id の情報を文字列に戻す
            $services = DB::table('services')->get();

            foreach ($services as $service) {
                if ($service->category_id) {
                    $category = DB::table('categories')
                        ->where('id', $service->category_id)
                        ->first();

                    if ($category) {
                        DB::table('services')
                            ->where('id', $service->id)
                            ->update(['category' => $category->name]);
                    }
                }
            }
        }
    }
};
