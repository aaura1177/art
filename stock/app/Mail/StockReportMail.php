<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Illuminate\Queue\SerializesModels;

class StockReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function build()
    {
        
        $message =  $this
                    ->subject('Stock Report '.date('d M Y'))
                    ->view('emails.report_mail');
        
        // if (file_exists(storage_path('app/public/invoices').'/'.$filename)){
        //     $file = storage_path('app/public/invoices').'/'.$filename;
        //     $message->attach($file);
        // }
        
        return $message->cc('dharmendra.prajapati6565@gmail.com');
        //return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}