<?php

namespace App\Mail;

use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

     /**
     * @var Request
     */
    public $mailrequest;

    public function __construct($mailrequest)
    {
        $this->request = $mailrequest;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'info@artisanadmin.net';
        $subject = $this->request['subject'];
        $message = $this->request['message'];
        $name = 'GlobalVision (P) Ltd';
        
        return $this->to($this->request['email'])
                    ->markdown('vendor.mail.html.message')
                    ->with(['slot'=>$message])
                    ->from($address, $name)
                    ->cc($address, $name)
                    ->bcc($address, $name)
                    ->replyTo($address, $name)
                    ->subject($subject);
        
    }
}
