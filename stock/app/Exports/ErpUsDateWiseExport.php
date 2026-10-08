<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\ErpUsHistory;
use App\ErpUsSheet;
use App\ErpUsProduct;

class ErpUsDateWiseExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct($fsd)
    {
        $this->fsd = $fsd;
    }

    public function query()
    {
    	$fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1);

        // $erp_history = ErpHistory::orderBy('created_at','asc')->get();
        // $i=8439;
        // foreach ($erp_history as $key => $value) {
        //     $value->id = $i;
        //     $value->save();
        //     $i++;
        // }
        // die;
        $products = ErpUsHistory::select('sku','stock')
                                ->whereDate('date','<=',$newfsd)
                                ->whereRaw('id IN (select MAX(id) FROM erp_us_history WHERE date <= "'.$newfsd.'" GROUP BY sku)')
                                ->orderBy('created_at','desc');
        
        return $products;
    }

    public function headings(): array {
        $fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1);
        $ErpSheetLessW = ErpUsSheet::whereDate('date','<=', $newfsd)->where('type','less')->orderby('id', 'DESC')->where('name','LIKE','%Wayfair%')->first();
        $ErpSheetLessI = ErpUsSheet::whereDate('date','<=', $newfsd)->where('type','less')->orderby('id', 'DESC')->where('name','LIKE','%IP%')->first();
        $ErpSheetAdd = ErpUsSheet::whereDate('date','<=', $newfsd)->where('type','add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx','',$ErpSheetAdd->name);
        $container_name = 'NA';
        if(isset($ErpSheetAdd->name)){
            $container_name = str_replace('invoiceTable ','',$name);
        }

        $wayfair_name  = str_replace('.csv','',$ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-','',$wayfair_name);

        $ip_name  = str_replace('.csv','',$ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-','',$ip_name);
        $h = 'Stock as on '.date('d-m-Y',strtotime($newfsd)).' (with container '.$container_name.' and upto csv #'.$wayfair_name.' & #'.$ip_name.')';
        $heading = ["SKU", "$h"];
        
        return $heading;
    }

    public function title(): string
    {
        return 'Erp Table Products';
    }

    public function map($product): array
    {

        return[
            $product->sku,
            number_format($product->stock)
        ];    
    }
}
