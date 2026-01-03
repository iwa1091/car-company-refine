<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;
    public ?string $cancelUrl;

    /**
     * @param Reservation   $reservation
     * @param string|null   $cancelUrl  キャンセル導線（任意）
     */
    public function __construct(Reservation $reservation, ?string $cancelUrl = null)
    {
        $this->reservation = $reservation;
        $this->cancelUrl = $cancelUrl;
    }

    public function build()
    {
        // Blade 側で service を参照しても落ちないように（未ロードならロード）
        try {
            $this->reservation->loadMissing('service');
        } catch (\Throwable $e) {
            // メール送信自体は続行
        }

        return $this->subject('【REFINE】ご予約ありがとうございます')
            ->view('emails.reservation-confirmed')
            ->with([
                'reservation' => $this->reservation,
                'cancelUrl'   => $this->cancelUrl,
            ]);
    }
}
