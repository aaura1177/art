<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\StockReportMail;
use Illuminate\Support\Facades\Mail;

class SendReportEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the report email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $data = [];

        $data['UkOldStock'] = 124;
        $data['UkNewStock'] = 10;
        $data['FurnitureStock'] = 23;
        $data['IndiaAccessoryStock'] = 20;
        $data['ChinaStock'] = 22;
        $data['TotalStock'] = 23;
        $data['FactoryStock'] = 22;
        $data['USA_IN2047'] = 12;
        $data['USA_IN2108'] = 34;
        $data['USA_REMAINING_STOCK'] = 32;
        $data['EU_REMAINING_STOCK'] = 33;

        Mail::to(['info@globalvisiondirect.co.uk','info@artisanfurniture.net'])->send(new StockReportMail($data));

       // $mail = new StockReportMail($data);
        //$mail->build();
        //var_dump($mail);
        $this->info('Report email sent successfully!');
    }
}
