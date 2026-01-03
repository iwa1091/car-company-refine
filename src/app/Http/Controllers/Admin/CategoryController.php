<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        // ✅ 前後の空白で重複扱いをすり抜けないように整形（不要なら削除OK）
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
            ],
            [
                'name.unique' => 'そのカテゴリー名はすでに登録されています。',
            ]
        );

        $category = Category::create($validated);

        // ✅ Inertia的に安定：redirect back + flash
        return redirect()
            ->back(303)
            ->with('success', 'カテゴリを作成しました。')
            ->with('category', $category);
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
        // ✅ 前後の空白で重複扱いをすり抜けないように整形（不要なら削除OK）
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('categories', 'name')->ignore($category->id),
                ],
            ],
            [
                'name.unique' => 'そのカテゴリー名はすでに登録されています。',
            ]
        );

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

    /**
     * 削除処理
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }
}
