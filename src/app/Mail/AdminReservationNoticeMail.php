<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminReservationNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;
    public ?string $cancelUrl;

    public function __construct(Reservation $reservation, ?string $cancelUrl = null)
    {
        $this->reservation = $reservation;
        $this->cancelUrl = $cancelUrl;
    }

    public function build()
    {
        // service を参照しても落ちにくくする（必要なら）
        try {
            $this->reservation->loadMissing('service');
        } catch (\Throwable $e) {
            // 続行
        }

        return $this->subject('【REFINE】新しい予約が入りました')
            ->view('emails.admin-reservation-notice');
            // publicプロパティなので with() は不要（ConfirmedMail と合わせるなら ConfirmedMail も with を削るのが綺麗）
    }
}
