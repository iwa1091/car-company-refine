<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\BusinessHour;      // 営業時間モデル
use App\Models\Customer;          // 顧客モデル
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * 予約フォーム表示（Inertia）
     */
    public function form(Request $request)
    {
        return Inertia::render('Reservation/ReservationForm', [
            'service_id' => $request->service_id ?? null,
        ]);
    }

    /**
     * 一般ユーザーの予約登録（user_id + customer_id 対応版）
     * 既存機能：
     * - 営業時間チェック
     * - ログインユーザーなら user_id / name / email を自動セット
     * - ゲストならフォームから name / email / phone を保存
     * - 予約完了後にマイページへリダイレクト
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'name'       => 'nullable|string',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string|max:20', // reservations テーブルに合わせる
            'date'       => 'required|date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'notes'      => 'nullable|string|max:500',
        ]);

        // ログインユーザー情報（ログインしていない場合は null）
        $user = Auth::user();

        // 営業時間を確認するために、予約日と曜日を取得
        $selectedDate = $request->date;
        $dayOfWeek    = Carbon::parse($selectedDate)->format('l'); // 例: Monday
        $businessHour = BusinessHour::where('day_of_week', $dayOfWeek)->first(); // 営業時間を取得

        // 営業時間内かどうかを確認
        if (
            $businessHour &&
            ($request->start_time < $businessHour->open_time || $request->end_time > $businessHour->close_time)
        ) {
            return redirect()
                ->back()
                ->withErrors(['時間は営業時間内で選択してください。']);
        }

        /**
         * 顧客情報の統一管理
         * - ログインユーザーの場合：User の情報をベースに使用
         * - ゲスト予約の場合：フォーム入力から顧客情報を作成
         */
        $baseName  = $user ? $user->name  : $request->name;
        $baseEmail = $user ? $user->email : $request->email;
        $basePhone = $user ? $user->phone : $request->phone;

        // メールアドレスをキーに Customer を作成 or 更新
        $customer = null;
        if ($baseEmail) {
            $customer = Customer::updateOrCreate(
                ['email' => $baseEmail],
                [
                    'name'  => $baseName,
                    'phone' => $basePhone,
                ]
            );
        }

        // 予約の作成（既存ロジックをベースに customer_id と phone を追加）
        $reservation = Reservation::create([
            'service_id' => $request->service_id,
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'notes'      => $request->notes,

            // ログインユーザーなら自動セット
            'user_id'     => $user ? $user->id : null,

            // 顧客テーブルへの紐付け（ある場合のみ）
            'customer_id' => $customer ? $customer->id : null,

            // 予約者情報（ゲスト予約 / ログインユーザー共通）
            'name'  => $baseName,
            'email' => $baseEmail,
            'phone' => $basePhone,

            // 初期ステータス（元の仕様を維持）
            'status' => 'confirmed',
        ]);

        // 顧客統計情報の更新（Customer モデルの recalculateStats を利用）
        if ($customer) {
            $customer->recalculateStats();
        }

        return redirect()
            ->route('mypage.index')
            ->with('success', 'ご予約を受け付けました！');
    }
}
