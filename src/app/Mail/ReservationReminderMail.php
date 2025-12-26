<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var \App\Models\Reservation
     */
    public Reservation $reservation;

    /**
     * ご予約日の何日前か（例：2, 1）
     *
     * @var int
     */
    public int $daysBefore;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, int $daysBefore)
    {
        $this->reservation = $reservation;
        $this->daysBefore  = $daysBefore;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = "【Lash Brow Ohana】ご予約日の{$this->daysBefore}日前のご案内";

        return $this
            ->subject($subject)
            ->view('emails.reservations.reminder');
    }
}
