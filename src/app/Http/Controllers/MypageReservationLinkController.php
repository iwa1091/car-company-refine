<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class MypageReservationLinkController extends Controller
{
    public function link(Request $request)
    {
        $request->validate([
            'reservation_code' => 'required|string',
        ]);

        $reservation = Reservation::where('reservation_code', $request->reservation_code)
            ->whereNull('user_id') // まだ誰にも紐付いていない
            ->first();

        if (!$reservation) {
            return back()->withErrors([
                'reservation_code' => '予約番号が見つからないか、すでに使用済みです。',
            ]);
        }

        $reservation->update([
            'user_id' => Auth::id(),  // ログインユーザーに紐付け
        ]);

        return back()->with('success', '予約をマイページに紐付けました！');
    }
}
