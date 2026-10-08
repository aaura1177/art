<?php

namespace App\Mail;

use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupplierReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $request;

    public function __construct(Request $request)
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
        $subject = 'GlobalVision - Pending Item Reminder Email!';
        $name = 'GlobalVision (P) Ltd';
        
        //$this->request['supplier_email']
        return $this->to("priyadev@autviz.in", $this->request['firstname'].' '.$this->request['lastname'])
                    ->view('emails.supp_reminder_mail')
                    ->from($address, $name)
                    ->cc($address, $name)
                    ->bcc($address, $name)
                    ->replyTo($address, $name)
                    ->subject($subject);
        
    }
}
