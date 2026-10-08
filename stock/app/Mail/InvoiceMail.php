<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
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
                    ->subject('Invoice '. $this->invoice->invoiceno)
                    ->view('emails.invoice_mail');
        $filename = str_replace('/', '-', $this->invoice->invoiceno) .'.pdf';
        // if (file_exists(storage_path('app/public/invoices').'/'.$filename)){
        //     $file = storage_path('app/public/invoices').'/'.$filename;
        //     $message->attach($file);
        // }
        
        return $message->cc(['accounts@artisanfurniture.net','backend@artisanfurniture.net','finance@artisanfurniture.net','inventory@artisanfurniture.net','kishoreasudani@gmail.com']);
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