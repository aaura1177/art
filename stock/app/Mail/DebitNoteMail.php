<?php

namespace App\Mail;

use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DebitNoteMail extends Mailable
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
    public $request;

    public function __construct( $request)
    {
        $this->request = $request;
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
            //    echo "<pre>";
            //    print_r($this->request);die;
        return  $this->to($this->request['email'])
                    ->view('emails.debit_note_mail')
                    ->from($address, $name)
                    ->cc($address, $name)
                    ->bcc($address, $name)
                    ->replyTo($address, $name)
                    ->subject($subject);

                    // echo "<pre>";
                    // print_r($test);die;
        
    }
}
