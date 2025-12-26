<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminReservationCanceledMail;
use App\Mail\UserReservationCanceledMail; // ✅ 追加
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserReservationController extends Controller
{
    /**
     * 📋 予約履歴一覧表示（Inertia.js対応）
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $reservations = Reservation::with('service')
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'service_name' => $r->service->name ?? '未設定',
                'date'         => Carbon::parse($r->date)->format('Y-m-d'),
                'time'         => Carbon::parse($r->start_time)->format('H:i'),
                'status'       => $r->status === 'canceled' ? 'キャンセル' : '確定',
            ]);

        return Inertia::render('Reservation/ReservationHistory', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * ❌ 予約キャンセル処理
     * 管理者・ユーザー双方にメール通知を送信
     */
    public function cancel($id, Request $request)
    {
        $reservation = Reservation::where('user_id', $request->user()->id)
            ->with('service')
            ->findOrFail($id);

        // すでにキャンセル済みか確認
        if ($reservation->status === 'canceled') {
            return back()->with('message', 'すでにキャンセル済みです。');
        }

        // ステータス更新
        $reservation->update(['status' => 'canceled']);

        try {
            // ============================
            // ✉️ 管理者へキャンセル通知
            // ============================
            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationCanceledMail($reservation));

            // ============================
            // ✉️ ユーザーへキャンセル通知
            // ============================
            Mail::to($reservation->email)->send(new UserReservationCanceledMail($reservation));

        } catch (\Exception $e) {
            // メール送信エラー時のログ出力
            Log::error('[キャンセル通知メール送信エラー]', [
                'reservation_id' => $reservation->id,
                'email' => $reservation->email ?? '不明',
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('message', '予約をキャンセルしました。');
    }
}
