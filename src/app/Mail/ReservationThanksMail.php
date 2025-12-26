<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationThanksMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var \App\Models\Reservation
     */
    public Reservation $reservation;

    /**
     * パターン（'after_3days' or 'after_1month'）
     *
     * @var string
     */
    public string $pattern;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, string $pattern)
    {
        $this->reservation = $reservation;
        $this->pattern     = $pattern;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = $this->pattern === 'after_3days'
            ? '【Lash Brow Ohana】ご来店ありがとうございました'
            : '【Lash Brow Ohana】次回ご来店のご案内';

        return $this
            ->subject($subject)
            ->view('emails.reservations.thanks');
    }
}
