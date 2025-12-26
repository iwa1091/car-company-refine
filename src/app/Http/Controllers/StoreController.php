<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StoreController extends Controller
{
    /**
     * 一般ユーザー向け商品一覧ページを表示
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // 🔍 検索機能
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 🔽 並び替え機能
        switch ($request->input('sort')) {
            case 'high_price':
                $query->orderBy('price', 'desc');
                break;
            case 'low_price':
                $query->orderBy('price', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // 📄 ページネーション
        $products = $query->paginate(12)->withQueryString();

        return view('online-store.index', compact('products'));
    }

    /**
     * 商品詳細ページを表示
     */
    public function show(Product $product)
    {
        return view('online-store.show', compact('product'));
    }

    /**
     * ✅ Stripe Checkout セッション作成
     * 在庫チェック → Stripe決済ページへ遷移
     */
    public function checkout(Request $request, Product $product)
    {
        // 🧱 ログイン必須
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', '購入にはログインが必要です。');
        }

        $user = Auth::user();

        // 🧱 在庫確認
        if ($product->stock <= 0) {
            return back()->with('error', '申し訳ありません、この商品は現在在庫切れです。');
        }

        try {
            // 🔑 Stripe 初期化
            Stripe::setApiKey(config('services.stripe.secret'));

            // ✅ Stripe Checkout セッションを作成
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $product->name,
                            // Stripeの説明は長すぎるとエラーになるので80文字程度に制限
                            'description' => mb_substr($product->description, 0, 80) . '...',
                        ],
                        // ✅ JPYの場合は ×100 しない
                        'unit_amount' => intval($product->price),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' => $user->email, // Stripeの顧客情報にメールを渡す
                'metadata' => [
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                ],
                // ✅ success_url で session_id を渡す（Webhook整合性のため）
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel'),
            ]);

            // ✅ Stripe Checkout ページへリダイレクト
            return redirect($session->url, 303);

        } catch (\Exception $e) {
            Log::error('Stripe Checkout Error', [
                'user_id'    => $user->id ?? null,
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', '決済ページの生成中にエラーが発生しました。');
        }
    }

    /**
     * ✅ Stripe 決済成功ページ
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        return view('checkout.success', [
            'message' => 'ご購入ありがとうございます！決済が完了しました。',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * ✅ Stripe 決済キャンセルページ
     */
    public function cancel()
    {
        return view('checkout.cancel', [
            'message' => '決済をキャンセルしました。',
        ]);
    }
}
