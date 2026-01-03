<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminReservationCanceledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // テンプレで service を参照しても落ちないように（未ロードならロード）
        try {
            $this->reservation->loadMissing('service');
        } catch (\Throwable $e) {
            // ここで落とす必要はない（メール送信自体は続行）
        }

        return $this->subject('【キャンセル通知】' . $this->reservation->name . '様の予約がキャンセルされました')
            ->view('emails.admin-reservation-canceled')
            ->with([
                'reservation' => $this->reservation,
            ]);
    }
}
