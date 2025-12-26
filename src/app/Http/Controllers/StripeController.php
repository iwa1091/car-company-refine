<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;   // ★ 顧客モデルを追加
use Carbon\Carbon;

class StripeController extends Controller
{
    /**
     * ✅ Stripe Checkout セッション作成
     */
    public function checkout(Request $request, Product $product)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // 在庫チェック
        if ($product->stock <= 0) {
            return back()->with('error', 'この商品は在庫切れです。');
        }

        try {
            $user = $request->user();

            // 念のため：未ログイン時はログイン画面へ
            if (!$user) {
                return redirect()->route('login')->with('error', '購入にはログインが必要です。');
            }

            // ★ ① ユーザー情報から Customer を作成 / 更新
            $customer = Customer::updateOrCreate(
                ['email' => $user->email],   // キー：メールアドレス
                [
                    'name'  => $user->name,
                    'phone' => $user->phone ?? null,
                ]
            );

            // ★ ② customer_id を含めて仮注文を作成
            $order = Order::create([
                'user_id'        => $user->id,
                'customer_id'    => $customer?->id,              // ← 顧客との紐づけを追加
                'product_id'     => $product->id,
                'order_number'   => strtoupper(uniqid('ORD')),
                'amount'         => $product->price,
                'currency'       => 'JPY',
                'payment_status' => 'pending',
                'ordered_at'     => Carbon::now(),
            ]);

            // ★ ③ Stripe セッション作成（metadata に order_id などを付与）
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name'        => $product->name,
                            'description' => $product->description,
                        ],
                        'unit_amount' => intval($product->price),
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('checkout.cancel'),
                'metadata'    => [
                    'order_id'     => $order->id,
                    'user_id'      => $user->id,
                    'user_email'   => $user->email,
                    'product_id'   => $product->id,
                    'customer_id'  => $customer?->id,  // ★ あとで Webhook 側で使うことも可能
                ],
            ]);

            // ✅ ④ Stripe Session の ID を保存
            $order->update([
                'stripe_session_id' => $session->id,
            ]);


            return redirect($session->url, 303);

        } catch (\Exception $e) {
            Log::error('Stripe Checkout Error', [
                'user_id'    => $request->user()->id ?? null,
                'product_id' => $product->id,
                'error'      => $e->getMessage()
            ]);

            return back()->with('error', '決済ページを開けませんでした。');
        }
    }
}
