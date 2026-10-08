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

class inventoryDateWiseReport implements FromView
{
	use Exportable;

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function view(): View
    {
        $fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1) . " 00:00:00";

    	$fed1 = strtotime($this->fed);
    	$newfed = date('Y-m-d',$fed1) . " 23:59:59";

        $products = product::get();
        
        foreach($products as $product){
            $pall[$product->id]['product'] = $product;
            $pall[$product->id]['stocklog_first'] = stockLog::where('created_at','<=', $newfsd)->where('product_id',$product->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
            if(empty($pall[$product->id]['stocklog_first'])){
                $pall[$product->id]['stocklog_first_2'] = stockLog::where('created_at','>=', $newfsd)->where('product_id',$product->id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->first();
            }
            $pall[$product->id]['stocklog_last'] = stockLog::where('created_at','<=', $newfed)->where('product_id',$product->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
            $pall[$product->id]['inward'] = stockLog::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->where('type',1)->where('ref_no','NOT LIKE','%SWAP%')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->sum('quantity');
            $pall[$product->id]['inward_swap'] = stockLog::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->where('type',1)->where('ref_no','LIKE','%SWAP%')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->sum('quantity');
            $pall[$product->id]['outward'] = stockLog::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->where('type',2)->where('ref_no','NOT LIKE','%SWAP%')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->sum('quantity');
            $pall[$product->id]['outward_swap'] = stockLog::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->where('type',2)->where('ref_no','LIKE','%SWAP%')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->sum('quantity');

            $pall[$product->id]['stockoutTable'] = stockoutTable::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->whereRaw('(SELECT invoice_id FROM stockout WHERE id = stockoutable.stock_id) > 0')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get()->toArray();

            $pall[$product->id]['pbTable_open'] = pbTable::selectRaw('*, (SELECT rate FROM pb_table WHERE created_at <= "'.$newfsd.' 23:59:59" AND product_id = '.$product->id.' AND rate > 1 ORDER BY created_at DESC LIMIT 1) as actual_rate')->where('created_at','<', $newfsd)->where('product_id',$product->id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get()->toArray();
            $pall[$product->id]['pbTable_close'] = pbTable::selectRaw('*, (SELECT rate FROM pb_table WHERE created_at <= "'.$newfed.' 23:59:59" AND product_id = '.$product->id.' AND rate > 1 ORDER BY created_at DESC LIMIT 1) as actual_rate')->where('created_at','<', $newfed)->where('product_id',$product->id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get()->toArray();

            $pall[$product->id]['pbTable'] = pbTable::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get()->toArray();

            $pall[$product->id]['inward_rate'] = pbTable::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$product->id)->where('rate','>',0)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->avg('rate');
        }
       
        return view('product.inventoryDateWiseReport', [
            'pall' => $pall,
            'start_date' => date('d M Y', $fsd1),
            'end_date' => date('d M Y', $fed1)
        ]);
    }

    
}
