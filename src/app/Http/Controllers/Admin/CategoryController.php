<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * カテゴリ一覧
     */
    public function index()
    {
        $categories = Category::orderBy('id', 'desc')->get();

        return Inertia::render('Admin/CategoryIndex', [
            'categories' => $categories,
        ]);
    }

    /**
     * 新規作成フォーム
     */
    public function create()
    {
        return Inertia::render('Admin/CategoryForm', [
            'category' => null,
        ]);
    }

    /**
     * 保存処理
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create($validated);

        // ✅ フラッシュデータに新しいカテゴリを渡す
        return back()->with('category', $category);
    }


    /**
     * 編集フォーム
     */
    public function edit(Category $category)
    {
        return Inertia::render('Admin/CategoryForm', [
            'category' => $category,
        ]);
    }

    /**
     * 更新処理
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

    /**
     * 削除処理
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }
}
