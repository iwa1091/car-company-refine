<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * =========================================================
     * 🧭 Inertia + API 共通対応
     * 顧客一覧表示（/admin/users or /api/admin/customers）
     * =========================================================
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        // 🔍 検索条件（名前・メール・電話）
        $query = Customer::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 🔢 ページネーション
        $customers = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        // 🧮 整形（Carbonなど）
        $customers->getCollection()->transform(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone ?? '—',
                'total_reservations' => $customer->total_reservations,
                'total_purchases' => $customer->total_purchases,
                'total_spent' => number_format($customer->total_spent) . ' 円',
                'last_reservation_at' => $customer->last_reservation_at
                    ? Carbon::parse($customer->last_reservation_at)->format('Y/m/d')
                    : '—',
                'last_purchase_at' => $customer->last_purchase_at
                    ? Carbon::parse($customer->last_purchase_at)->format('Y/m/d')
                    : '—',
                'memo' => $customer->memo,
            ];
        });

        // ✅ JSONリクエスト（API経由）の場合
        if ($request->wantsJson()) {
            return Response::json([
                'data' => $customers,
                'filters' => ['search' => $search],
            ]);
        }

        // ✅ Inertiaページ（/admin/users）表示
        return Inertia::render('Admin/UserList', [
            'customers' => $customers,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * =========================================================
     * 📄 顧客詳細表示（API or Inertia）
     * =========================================================
     */
    public function show($id, Request $request)
    {
        $customer = Customer::findOrFail($id);

        $data = [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'total_reservations' => $customer->total_reservations,
            'total_purchases' => $customer->total_purchases,
            'total_spent' => number_format($customer->total_spent) . ' 円',
            'last_reservation_at' => $customer->last_reservation_at
                ? Carbon::parse($customer->last_reservation_at)->format('Y/m/d')
                : '—',
            'last_purchase_at' => $customer->last_purchase_at
                ? Carbon::parse($customer->last_purchase_at)->format('Y/m/d')
                : '—',
            'memo' => $customer->memo,
            'created_at' => $customer->created_at->format('Y/m/d H:i'),
            'updated_at' => $customer->updated_at->format('Y/m/d H:i'),
        ];

        if ($request->wantsJson()) {
            return Response::json($data);
        }

        return Inertia::render('Admin/UserShow', ['customer' => $data]);
    }

    /**
     * =========================================================
     * ✏️ 顧客メモ・情報更新（API対応）
     * =========================================================
     */
    public function update($id, Request $request)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'memo' => 'nullable|string|max:1000',
        ]);

        $customer->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => '顧客情報を更新しました。',
                'customer' => $customer,
            ]);
        }

        return redirect()->back()->with('message', '顧客情報を更新しました。');
    }

    /**
     * =========================================================
     * ❌ 顧客削除（API対応）
     * =========================================================
     */
    public function destroy($id, Request $request)
    {
        $customer = Customer::findOrFail($id);

        try {
            $customer->delete();
            $message = '顧客を削除しました。';
        } catch (\Exception $e) {
            Log::error('[顧客削除エラー]', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $message = '削除に失敗しました。';
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->back()->with('message', $message);
    }
}
