<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer; // ★ 追加：顧客モデル
use App\Mail\AdminOrderNotificationMail;
use App\Mail\UserOrderConfirmationMail;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    /**
     * ✅ Stripe から送信される Webhook を処理
     */
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        // Webhook Secret は config/services.php 経由で取得
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('❌ Invalid Stripe payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('❌ Invalid Stripe signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        switch ($event->type) {

            case 'checkout.session.completed':
                $session = $event->data->object;
                Log::info('✅ Checkout completed Webhook received', ['session_id' => $session->id]);

                // 1. Stripe Session IDを使って既存の仮注文を検索する (重複作成防止)
                $order = Order::where('stripe_session_id', $session->id)
                    ->where('payment_status', 'pending')
                    ->first();

                if (!$order) {
                    // 注文が既に見つからない、または既に処理済み（paidなど）の場合、Stripeに200を返す
                    Log::warning('⚠️ Order not found or already processed.', ['session_id' => $session->id]);
                    return response('Order processed or not found', 200);
                }

                $product = $order->product;

                if (!$product) {
                    Log::error('❌ Product not found for order.', ['order_id' => $order->id]);
                    return response('Product not found for order', 404);
                }

                // 2. 注文ステータスを paid に更新
                $order->update([
                    'payment_status'    => 'paid',
                    'stripe_payment_id' => $session->payment_intent ?? null,
                    // 配送先情報をStripeセッション情報で上書き（または既存の値を維持）
                    'shipping_name'     => $session->customer_details->name
                        ?? $order->shipping_name,
                    'shipping_address'  => $session->customer_details->address->line1
                        ?? $order->shipping_address,
                    'shipping_phone'    => $session->customer_details->phone
                        ?? $order->shipping_phone,
                    // amount と currency は仮注文作成時に設定済みのため更新は不要
                ]);
                Log::info('✅ Order status updated to PAID.', ['order_id' => $order->id]);

                // 3. ✅ 在庫引き落とし
                if ($product->stock > 0) {
                    $product->decrement('stock', 1);
                    Log::info('✅ Product stock decremented.', ['product_id' => $product->id]);
                }

                // 4. ✅ 顧客統計情報の更新（購入数・総支出・最終購入日）
                try {
                    $customer = null;

                    // 💡 優先：Order に customer リレーションがある場合
                    if (method_exists($order, 'customer') && $order->customer) {
                        $customer = $order->customer;
                    } else {
                        // Fallback：メールアドレスから Customer を検索
                        $emailFromSession = $session->customer_details->email ?? null;
                        $emailFromUser    = $order->user->email ?? null;
                        $email            = $emailFromSession ?? $emailFromUser;

                        if ($email) {
                            $customer = Customer::where('email', $email)->first();
                        }
                    }

                    if ($customer && method_exists($customer, 'recalculateStats')) {
                        $customer->recalculateStats();
                        Log::info('👤 Customer stats recalculated.', [
                            'customer_id' => $customer->id,
                        ]);
                    } else {
                        Log::info('ℹ️ Customer not found or recalculateStats missing. Stats not updated.', [
                            'order_id' => $order->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('❌ Failed to recalc customer stats', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                // 5. ✅ メール送信 (アドレスの確実な取得とログの強化)
                try {
                    $adminEmail = env('MAIL_ADMIN_ADDRESS');

                    // ユーザーメールアドレスは、Stripeセッション または 紐づくユーザー情報から取得
                    $customerEmail = $session->customer_details->email
                        ?? ($order->user->email ?? null);

                    // 管理者メール
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new AdminOrderNotificationMail($order));
                        Log::info('📧 Admin email scheduled.', ['to' => $adminEmail]);
                    } else {
                        Log::error('❌ MAIL_ADMIN_ADDRESS is not set. Admin email skipped.');
                    }

                    // ユーザーメール
                    if ($customerEmail) {
                        Mail::to($customerEmail)->send(new UserOrderConfirmationMail($order));
                        Log::info('📧 User confirmation email scheduled.', ['to' => $customerEmail]);
                    } else {
                        Log::warning('⚠️ Customer email not found. User confirmation email skipped.');
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send emails', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                break;

            default:
                Log::warning('⚠️ Unhandled Stripe event', ['type' => $event->type]);
        }

        return response('✅ Webhook processed', 200);
    }
}
