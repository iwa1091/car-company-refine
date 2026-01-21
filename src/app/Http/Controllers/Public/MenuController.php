<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * 公開メニュー（カテゴリ->サービスのネスト）
     * GET /api/public/menu
     */
    public function apiPublicMenu()
    {
        $categories = Category::with(['services' => function ($q) {
            $q->where('is_active', true)
              ->orderBy('sort_order', 'asc')
              ->orderByDesc('id');
        }])
        ->where('is_active', true)
        ->orderBy('sort_order', 'asc')
        ->get();

        $payload = $categories->map(function ($cat) {
            $services = $cat->services->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'description' => $s->description,
                    'duration_minutes' => (int) $s->duration_minutes,
                    'is_popular' => (bool) $s->is_popular,
                    'features' => $s->features ?? [],
                    'image_url' => $s->image ? Storage::url($s->image) : null,
                ];
            })->values();

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'services' => $services,
            ];
        })->values();

        // Blade互換：空カテゴリは Next 側で落としてOK（ここで落としてもOK）
        return response()->json([
            'categories' => $payload,
        ]);
    }
}
