<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * カテゴリ一覧（Inertia）
     */
    public function index()
    {
        $categories = Category::orderBy('id', 'desc')->get();

        return Inertia::render('Admin/CategoryIndex', [
            'categories' => $categories,
        ]);
    }

    /**
     * 新規作成フォーム（Inertia）
     */
    public function create()
    {
        return Inertia::render('Admin/CategoryForm', [
            'category' => null,
        ]);
    }

    /**
     * 保存処理（Inertia）
     */
    public function store(Request $request)
    {
        // ✅ 前後の空白で重複扱いをすり抜けないように整形
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

        // ✅ 既存を壊さない＆カラム差分にも強くする（mass assignment に依存しない）
        $category = $this->createCategoryWithDefaults($validated['name']);

        return redirect()
            ->back(303)
            ->with('success', 'カテゴリを作成しました。')
            ->with('category', $category);
    }

    /**
     * 編集フォーム（Inertia）
     */
    public function edit(Category $category)
    {
        return Inertia::render('Admin/CategoryForm', [
            'category' => $category,
        ]);
    }

    /**
     * 更新処理（Inertia）
     */
    public function update(Request $request, Category $category)
    {
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
     * 削除処理（Inertia）
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }

    // =====================================================
    // ✅ Next.js / API 用（CategoryModal が叩く）
    // =====================================================

    /**
     * ✅ 管理者用：カテゴリ一覧API（JSON）
     * GET /api/admin/categories
     */
    public function apiIndex()
    {
        $query = Category::query();

        // sort_order があるなら並び順を安定させる（無ければ id でOK）
        if (Schema::hasColumn('categories', 'sort_order')) {
            $query->orderBy('sort_order', 'asc');
        }
        $query->orderBy('id', 'asc');

        return response()->json(
            $query->get(['id', 'name'])
        );
    }

    /**
     * ✅ 管理者用：カテゴリ作成API（JSON）
     * POST /api/admin/categories
     *
     * return:
     *  { "category": { "id": 1, "name": "..." } }
     */
    public function apiStore(Request $request)
    {
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

        $category = $this->createCategoryWithDefaults($validated['name']);

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ], 201);
    }

    /**
     * ✅ カラム差分に強いカテゴリ作成（sort_order / is_active がNOT NULLでも事故りにくい）
     */
    private function createCategoryWithDefaults(string $name): Category
    {
        $category = new Category();
        $category->name = $name;

        // sort_order が存在するなら末尾に追加（NOT NULL でも安全）
        if (Schema::hasColumn('categories', 'sort_order')) {
            $max = Category::max('sort_order');
            $category->sort_order = is_numeric($max) ? ((int) $max + 1) : 1;
        }

        // is_active が存在するなら true を入れておく（NOT NULL でも安全）
        if (Schema::hasColumn('categories', 'is_active')) {
            $category->is_active = true;
        }

        $category->save();

        return $category;
    }
}
