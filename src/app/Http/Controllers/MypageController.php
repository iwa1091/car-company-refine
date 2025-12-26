<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Reservation;
use App\Models\Order;

class MypageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 念のため：認証ミドルウェアが付いているが、null ガードも入れておく
        if (!$user) {
            // 万が一ここに来てもログイン画面へ
            return redirect()->route('login');
        }

        // 🕘 過去の予約（今日より前）
        $pastReservations = Reservation::where('user_id', $user->id)
            ->where('date', '<', now()->toDateString())
            ->with('service')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        // 📅 これからの予約（今日以降）
        $upcomingReservations = Reservation::where('user_id', $user->id)
            ->where('date', '>=', now()->toDateString())
            ->with('service')
            ->orderBy('date', 'asc')
            ->get();

        // 🛍 購入履歴：支払い済み（paid）の注文のみ取得
        $pastOrders = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')   // ★ ここを追加：決済完了のみ
            ->with('product')
            ->orderBy('ordered_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Mypage/Index', [
            'user'                 => $user,
            'pastReservations'     => $pastReservations,
            'pastOrders'           => $pastOrders,
            'upcomingReservations' => $upcomingReservations,
        ]);
    }
}
