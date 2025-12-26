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
     * 管理者用サービス一覧
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
     * 一般ユーザー向けサービス一覧
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
     * 新規作成フォーム
     */
    public function create()
    {
        return Inertia::render('Admin/ServiceForm', [
            'service' => null,
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * サービス登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'price' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required',
            'is_popular' => 'nullable',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:10240',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
        ]);

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
     * 編集フォーム
     */
    public function edit(Service $service)
    {
        $serviceData = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'price' => $service->price,
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
     * サービス更新
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'price' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required',
            'is_popular' => 'nullable',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
        ]);

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
     * 削除処理
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
     * 公開・非公開切り替え (AJAX)
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
        $services = Service::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'price', 'duration_minutes']);

        return response()->json($services);
    }

}
