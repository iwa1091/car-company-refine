<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Service;      // 使う可能性があるので残しておく
use App\Models\BusinessHour;
use App\Models\Customer;     // 顧客モデル
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AdminReservationController extends Controller
{
    /**
     * -------------------------------------------------------------
     * 🖥 管理画面（Inertia）用：予約一覧
     * -------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $reservations = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15)
            ->through(fn ($r) => [
                'id'            => $r->id,
                'date'          => $r->date,
                'start_time'    => $r->start_time,
                'end_time'      => $r->end_time,
                'name'          => $r->name,
                'email'         => $r->email,

                // ✅ 追加：管理画面一覧で使う項目（- 表示の原因だった）
                'phone'         => $r->phone,
                'maker'         => $r->maker,
                'car_model'     => $r->car_model,
                'course'        => $r->course,

                'status'        => $r->status,
                'notes'         => $r->notes,
                'service_name'  => $r->service?->name,
                'duration'      => $r->service?->duration_minutes,  // 所要時間も表示
                'user_id'       => $r->user_id,
                'user_name'     => $r->user?->name,
            ]);

        return Inertia::render('Admin/ReservationList', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * 🟦 API用：予約一覧（React管理画面の fetch 用）
     * GET /api/admin/reservations
     * -------------------------------------------------------------
     */
    public function apiIndex()
    {
        $reservations = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'date'          => $r->date,
                'start_time'    => $r->start_time,
                'end_time'      => $r->end_time,
                'name'          => $r->name,
                'email'         => $r->email,

                // ✅ 追加：ReservationList.jsx が表示している項目（ここが欠けていた）
                'phone'         => $r->phone,
                'maker'         => $r->maker,
                'car_model'     => $r->car_model,
                'course'        => $r->course,

                'status'        => $r->status,
                'notes'         => $r->notes,
                'service_name'  => $r->service?->name,
                'duration'      => $r->service?->duration_minutes, // 所要時間も返す
                'user_id'       => $r->user_id,
                'user_name'     => $r->user?->name,
            ]);

        return response()->json($reservations);
    }

    /**
     * -------------------------------------------------------------
     * 🟦 API用：予約削除
     * DELETE /api/admin/reservations/{id}
     * -------------------------------------------------------------
     */
    public function apiDestroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        // 削除前に顧客IDを控えておく
        $customerId = $reservation->customer_id;

        $reservation->delete();

        // 紐づく顧客の統計情報を再計算
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return response()->json([
            'message' => '予約を削除しました',
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ✏️ 管理画面：予約編集ページ
     * -------------------------------------------------------------
     */
    public function edit($id)
    {
        // 予約情報 + サービス・ユーザーを取得
        $reservation = Reservation::with(['service', 'user'])
            ->findOrFail($id);

        // 予約日の年月に合わせて営業時間を取得（将来的に使う場合のために保持）
        $targetDate = Carbon::parse($reservation->date);

        $businessHours = BusinessHour::where('year', $targetDate->year)
            ->where('month', $targetDate->month)
            ->get();

        return Inertia::render('Admin/ReservationEdit', [
            'reservation' => [
                'id'           => $reservation->id,
                'date'         => $reservation->date,
                'start_time'   => $reservation->start_time,
                'end_time'     => $reservation->end_time,
                'name'         => $reservation->name,
                'email'        => $reservation->email,
                'status'       => $reservation->status,
                'notes'        => $reservation->notes,

                // ✅ 追加：編集画面でも必要になる可能性があるので保持（不一致防止・害なし）
                'phone'        => $reservation->phone,
                'maker'        => $reservation->maker,
                'car_model'    => $reservation->car_model,
                'course'       => $reservation->course,

                // サービス関連
                'service_id'   => $reservation->service_id,
                'service_name' => $reservation->service?->name,
                'duration'     => $reservation->service?->duration_minutes,

                // フロントが期待しているネスト構造（ReservationEdit.jsx 用）
                'service' => $reservation->service ? [
                    'id'               => $reservation->service->id,
                    'name'             => $reservation->service->name,
                    'duration_minutes' => $reservation->service->duration_minutes,
                ] : null,

                // ユーザー情報
                'user_id'   => $reservation->user_id,
                'user_name' => $reservation->user?->name,
                'user'      => $reservation->user ? [
                    'id'   => $reservation->user->id,
                    'name' => $reservation->user->name,
                ] : null,
            ],

            // 今は ReservationEdit.jsx 側で /api/business-hours を叩いていますが、
            // 将来的に Inertia 経由で渡したい場合に備えて残しておきます。
            'businessHours' => $businessHours,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ♻️ 管理画面：予約更新
     *   PUT /admin/reservations/{id}
     *   route name: admin.reservations.update
     * -------------------------------------------------------------
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        // ReservationEdit.jsx から送られてくる項目に合わせてバリデーション
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],   // "HH:MM" を想定
            'service_id' => ['required', 'exists:services,id'],
            // service_duration は信頼せず、サービスから再取得する
        ]);

        // サービスの施術時間から end_time を再計算
        $service = Service::findOrFail($validated['service_id']);

        // "Y-m-d H:i" 形式で結合して Carbon に渡す
        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'] . ' ' . $validated['start_time']
        );
        $endDateTime   = (clone $startDateTime)->addMinutes($service->duration_minutes);

        // 🔁 他の予約との重複チェック（自分自身は除外）
        $isOverlapping = Reservation::where('date', $validated['date'])
            ->where('status', 'confirmed')
            ->where('id', '!=', $reservation->id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->where('start_time', '<', $endDateTime->format('H:i:s'))
                      ->where('end_time', '>', $startDateTime->format('H:i:s'));
            })
            ->exists();

        if ($isOverlapping) {
            return back()
                ->withErrors([
                    'start_time' => '指定された時間帯は他の予約と重複しています。',
                ])
                ->withInput();
        }

        // 予約情報を更新（email / notes / status はこの画面では変更しない想定）
        $reservation->update([
            'name'       => $validated['name'],
            'date'       => $validated['date'],
            'start_time' => $startDateTime->format('H:i:s'),
            'end_time'   => $endDateTime->format('H:i:s'),
            'service_id' => $validated['service_id'],
        ]);

        // 紐づく顧客の統計情報を再計算
        if ($reservation->customer_id) {
            $customer = Customer::find($reservation->customer_id);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', '予約内容を更新しました');
    }

    /**
     * -------------------------------------------------------------
     * 🗑 Inertia 用の削除
     * -------------------------------------------------------------
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $customerId  = $reservation->customer_id;

        $reservation->delete();

        // 紐づく顧客の統計情報を再計算
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', '予約を削除しました');
    }
}
