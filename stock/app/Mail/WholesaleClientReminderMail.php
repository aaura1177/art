<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WholesaleClientReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        $address = 'info@artisanadmin.net';
        $name = 'GlobalVision (P) Ltd';
        $buyer = $this->payload['buyer_orderno'] ?? '';
        $subject = 'Wholesale pending reminder — ' . $buyer;

        $mail = $this->view('emails.wholesale_client_reminder')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject);

        foreach ($this->payload['recipients'] as $r) {
            $mail->to($r['email'], $r['name'] ?? null);
        }

        return $mail;
    }
}
