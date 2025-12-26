<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data; // バリデーション済みデータ
    }

    public function build()
    {
        return $this->subject('【Ohana】お問い合わせが届きました')
            ->view('emails.contact')
            ->with(['data' => $this->data]);
    }
}
