<?php

namespace App\Mail;

use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OnePageEmail extends Mailable
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
        $subject = 'New Partner Signup';
        $name = 'GV-Pricing';
        if($this->request->hasfile('product_photos')) { 
            $files = $this->request->file('product_photos');
            $images = array();
            foreach($files as $file){
                $this->attach($file->getRealPath(), array(
                    'as' => $file->getClientOriginalName(),    
                    'mime' => $file->getMimeType())
                );
            }
        }
        return $this->to('partner@artisanfurniture.net', 'Artisan Furniture')
                    ->view('emails.contact_mail')
                    ->from($address, $name)
                    ->cc($address, $name)
                    ->bcc($address, $name)
                    ->replyTo($address, $name)
                    ->subject($subject);
        
    }
}
