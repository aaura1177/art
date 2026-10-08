<?php

namespace App\Exports;

use App\stockoutTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\stockLog;
use App\pbTable;
use App\product;

class productAnnualReport implements FromView
{
	use Exportable;

    public function __construct($fsd, $fed, $product_id)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
        $this->product_id = $product_id;
    }

    public function view(): View
    {
        $fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1) . " 00:00:00";

    	$fed1 = strtotime($this->fed);
    	$newfed = date('Y-m-d',$fed1) . " 23:59:59";

        $product = product::find($this->product_id);

        $stocklog = stockLog::where('created_at', '>=', $newfsd)->where('created_at', '<=', $newfed)->where('product_id',$this->product_id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

        //dd($stocklog);

        $open_balance = 0;

        $openin_balance = stockLog::whereDate('created_at','<=', $newfsd)->where('product_id',$this->product_id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();

        if(isset($openin_balance->id)){
            $open_balance = $openin_balance->remaining_stock;
        }

        $open_rate = 0;

        $pb = \App\pbTable::selectRaw('*, (SELECT rate FROM pb_table WHERE created_at <= "'.$newfsd.' 23:59:59" AND product_id = '.$product->id.' AND rate > 1 ORDER BY created_at DESC LIMIT 1) as actual_rate')->where('product_id',$this->product_id)->whereDate('created_at','<=', $newfsd)->get();

        if($open_balance > 0){                    
            $ins        =   [];
            foreach($pb as $inn){
                if(isset($inn->id)){
                    $ins[$inn->id]['qty']       =   $inn->receiveqty;
                    $ins[$inn->id]['rate']      =   $inn->actual_rate;
                }
            }
            krsort($ins);
            $qty = 0;
            $amount = 0;
            $remq = $open_balance;
            foreach($ins as $in){
                $qty = $qty+$in['qty'];
                if($qty > $open_balance){
                    $amount = $amount + $in['rate']*$remq;
                    break;
                }else{
                    $remq = $remq - $in['qty'];
                    $amount = $amount + ($in['rate']*$in['qty']);
                }
            }
            if($amount > 0){
                $open_rate = $amount/$open_balance;
            }
        }

        if(isset($pb->id)){
            $open_rate = $pb->rate;
        }
        

        return view('product.productAnnualReport', [
            'stocklog' => $stocklog,
            'product'  => $product,
            'open_balance'=>$open_balance,
            'open_rate'=>$open_rate,
            'newfsd'=>$newfsd
        ]);
    }

    
}
