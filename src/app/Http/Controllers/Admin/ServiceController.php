<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * 管理者用サービス一覧（Inertia）
     */
    public function index()
    {
        $services = Service::with('category')
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get();

        $serviceData = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,

                // ✅ 追加
                'price_text' => $service->price_text,

                'sort_order' => $service->sort_order,
                'is_active' => $service->is_active,
                'is_popular' => $service->is_popular,
                'category' => $service->category->name ?? '未分類',
                'category_id' => $service->category_id,
                'image_url' => $service->image ? Storage::url($service->image) : null,
                'features' => $service->features ?? [],
            ];
        });

        return Inertia::render('Admin/ServiceIndex', [
            'services' => $serviceData,
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * 一般ユーザー向けサービス一覧（Blade）
     */
    public function publicIndex()
    {
        $categories = Category::with(['services' => function ($query) {
            $query->where('is_active', true)
                  ->orderBy('sort_order', 'asc');
        }])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('menu_price', compact('categories'));
    }

    /**
     * 新規作成フォーム（Inertia）
     */
    public function create()
    {
        return Inertia::render('Admin/ServiceForm', [
            'service' => null,
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * サービス登録（Inertia）
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255|unique:services,name',
                'description' => 'nullable|string',
                'duration_minutes' => 'required|integer|min:1|max:480',
                'price' => 'required|integer|min:0',

                // ✅ 追加
                'price_text' => 'nullable|string|max:255',

                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'required',
                'is_popular' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|max:200',
                'features' => 'nullable|array',
                'features.*' => 'string|max:255',
            ],
            [
                'name.unique' => 'このメニュー名は既に登録されています。',
                'image.max' => '画像は200KB以下にしてください。',
            ]
        );

        // ✅ booleanを安全に変換（Inertiaは文字列で送るため）
        $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $validated['is_popular'] = filter_var($request->input('is_popular'), FILTER_VALIDATE_BOOLEAN);

        // ✅ features が null の場合は空配列に統一
        $validated['features'] = $validated['features'] ?? [];

        // ✅ 画像アップロード処理
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを作成しました。');
    }

    /**
     * 編集フォーム（Inertia）
     */
    public function edit(Service $service)
    {
        $serviceData = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'price' => $service->price,

            // ✅ 追加
            'price_text' => $service->price_text,

            'sort_order' => $service->sort_order,
            'is_active' => $service->is_active,
            'is_popular' => $service->is_popular,
            'category_id' => $service->category_id,
            'features' => $service->features ?? [],
            'image_url' => $service->image ? Storage::url($service->image) : null,
        ];

        return Inertia::render('Admin/ServiceForm', [
            'service' => $serviceData,
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * サービス更新（Inertia）
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255|unique:services,name,' . $service->id,
                'description' => 'nullable|string',
                'duration_minutes' => 'required|integer|min:1|max:480',
                'price' => 'required|integer|min:0',

                // ✅ 追加
                'price_text' => 'nullable|string|max:255',

                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'required',
                'is_popular' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|max:200',
                'features' => 'nullable|array',
                'features.*' => 'string|max:255',
            ],
            [
                'image.max' => '画像は200KB以下にしてください。',
            ]
        );

        $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $validated['is_popular'] = filter_var($request->input('is_popular'), FILTER_VALIDATE_BOOLEAN);
        $validated['features'] = $validated['features'] ?? [];

        // ✅ 画像再アップロード時は古い画像削除
        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを更新しました。');
    }

    /**
     * 削除処理（Inertia）
     */
    public function destroy(Service $service)
    {
        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを削除しました。');
    }

    /**
     * 公開・非公開切り替え（Inertia/AJAX）
     */
    public function toggleActive(Service $service)
    {
        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'success' => true,
            'is_active' => $service->is_active,
        ]);
    }

    /**
     * 一般ユーザー向け：サービス一覧API（JSON）
     */
    public function apiList()
    {
        $services = Service::with(['category:id,name'])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'price', 'price_text', 'duration_minutes', 'category_id']);

        $payload = $services->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'price' => $s->price,

                // ✅ 追加
                'price_text' => $s->price_text,

                'duration_minutes' => $s->duration_minutes,
                'category_id' => $s->category_id,
                'category_name' => optional($s->category)->name,
            ];
        });

        return response()->json($payload);
    }

    /**
     * ✅ 管理者用：サービス一覧API（Next.js用）
     * GET /api/admin/services
     */
    public function apiIndex()
    {
        $services = Service::with('category')
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get();

        $serviceData = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'price_text' => $service->price_text,
                'sort_order' => $service->sort_order,
                'is_active' => (bool) $service->is_active,
                'is_popular' => (bool) $service->is_popular,
                'category_id' => $service->category_id,
                'category' => $service->category->name ?? '未分類',
                'image_url' => $service->image ? Storage::url($service->image) : null,
                'features' => $service->features ?? [],
            ];
        });

        $categories = Category::orderBy('sort_order')->get(['id', 'name']);

        return response()->json([
            'services' => $serviceData,
            'categories' => $categories,
        ]);
    }

    /**
     * ✅ 管理者用：サービス詳細API（Next.js 編集画面で必要）
     * GET /api/admin/services/{service}
     */
    public function apiShow(Service $service)
    {
        // category 名も返す（一覧と互換）
        $service->loadMissing('category:id,name');

        $serviceData = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'price' => $service->price,
            'price_text' => $service->price_text,
            'sort_order' => $service->sort_order,
            'is_active' => (bool) $service->is_active,
            'is_popular' => (bool) $service->is_popular,
            'category_id' => $service->category_id,
            'category' => $service->category->name ?? '未分類',
            'image_url' => $service->image ? Storage::url($service->image) : null,
            'features' => $service->features ?? [],
        ];

        // 編集フォームでカテゴリー選択が必要になりがちなので同梱（不要ならフロントで無視OK）
        $categories = Category::orderBy('sort_order')->get(['id', 'name']);

        return response()->json([
            'service' => $serviceData,
            'categories' => $categories,
        ]);
    }

    /**
     * ✅ 管理者用：サービス作成API（Next.js用）
     * POST /api/admin/services
     */
    public function apiStore(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255|unique:services,name',
                'description' => 'nullable|string|max:2000',
                'duration_minutes' => 'required|integer|min:1|max:480',
                'price' => 'required|integer|min:0',
                'price_text' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'required',
                'is_popular' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|max:200',
                'features' => 'nullable|array',
                'features.*' => 'string|max:255',
            ],
            [
                'name.unique' => 'このメニュー名は既に登録されています。',
                'image.max' => '画像は200KB以下にしてください。',
            ]
        );

        $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $validated['is_popular'] = filter_var($request->input('is_popular'), FILTER_VALIDATE_BOOLEAN);
        $validated['features'] = $validated['features'] ?? [];

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service = Service::create($validated);
        $service->loadMissing('category:id,name');

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'price_text' => $service->price_text,
                'sort_order' => $service->sort_order,
                'is_active' => (bool) $service->is_active,
                'is_popular' => (bool) $service->is_popular,
                'category_id' => $service->category_id,
                'category' => $service->category->name ?? '未分類',
                'image_url' => $service->image ? Storage::url($service->image) : null,
                'features' => $service->features ?? [],
            ],
        ], 201);
    }

    /**
     * ✅ 管理者用：サービス更新API（Next.js用）
     * PUT /api/admin/services/{service}
     */
    public function apiUpdate(Request $request, Service $service)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255|unique:services,name,' . $service->id,
                'description' => 'nullable|string|max:2000',
                'duration_minutes' => 'required|integer|min:1|max:480',
                'price' => 'required|integer|min:0',
                'price_text' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'required',
                'is_popular' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|max:200',
                'features' => 'nullable|array',
                'features.*' => 'string|max:255',
            ],
            [
                'image.max' => '画像は200KB以下にしてください。',
            ]
        );

        $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $validated['is_popular'] = filter_var($request->input('is_popular'), FILTER_VALIDATE_BOOLEAN);
        $validated['features'] = $validated['features'] ?? [];

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($validated);
        $service->loadMissing('category:id,name');

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'price_text' => $service->price_text,
                'sort_order' => $service->sort_order,
                'is_active' => (bool) $service->is_active,
                'is_popular' => (bool) $service->is_popular,
                'category_id' => $service->category_id,
                'category' => $service->category->name ?? '未分類',
                'image_url' => $service->image ? Storage::url($service->image) : null,
                'features' => $service->features ?? [],
            ],
        ]);
    }

    /**
     * ✅ 管理者用：サービス削除API（Next.js用）
     * DELETE /api/admin/services/{service}
     */
    public function apiDestroy(Service $service)
    {
        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * ✅ 管理者用：公開/非公開トグルAPI（Next.jsの /toggle 用）
     * PATCH /api/admin/services/{service}/toggle
     */
    public function apiToggle(Service $service)
    {
        $service->is_active = !$service->is_active;
        $service->save();
        $service->loadMissing('category:id,name');

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'category_id' => $service->category_id,
                'category' => $service->category->name ?? '未分類',
                'duration_minutes' => $service->duration_minutes,
                'features' => $service->features ?? [],
                'is_popular' => (bool) $service->is_popular,
                'is_active' => (bool) $service->is_active,
            ],
        ]);
    }
}
