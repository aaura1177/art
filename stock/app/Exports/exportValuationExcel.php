<?php

namespace App\Exports;

use App\product;
use App\hardwares;
use App\pricingTable;
use App\pbTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class exportValuationExcel implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;

    public function __construct($from)
    {
        $this->from = $from;
    }

    public function collection() {
		$from = $this->from;
        $products	=	product::selectRaw('*,(SELECT remaining_stock FROM stock_log WHERE product_id=product_table.id AND created_at <= "'.$from. ' 23:59:59" ORDER BY created_at desc, id desc LIMIT 1) as remaining_stock')->orderBy('code','ASC')->get();
		foreach($products as $key=>$product){
            $products[$key]['pbs'] = pbTable::selectRaw('*, (SELECT rate FROM pb_table WHERE created_at <= "'.$from.' 23:59:59" AND product_id = '.$product->id.' AND rate > 1 ORDER BY created_at DESC LIMIT 1) as actual_rate')->where('created_at','<=',$from. ' 23:59:59')->where('product_id',$product->id)->get();
        }
        return $products;
    }

    public function headings(): array {
        return [
           "Product Code","Product Name","Quantity","Rate","Amount"
        ];
    }

    public function map($product): array
    {
		if($product->remaining_stock < 0){
			$remaining =	0;
		}else{
			$remaining =	$product->remaining_stock;
		}
		$ins		=	[];
		$rates		=	[];
		$last 			=	0;
		foreach($product['pbs'] as $inn){
			$ins[$inn->id]['qty']		=	$inn->receiveqty;
			$ins[$inn->id]['rate']		=	$inn->actual_rate;
		}
		krsort($ins);
		$qty = 0;
		$amount = 0;
		$remq = $product->remaining_stock;
		foreach($ins as $in){
			$qty = $qty+$in['qty'];
			if($qty > $product->remaining_stock){
				$amount = $amount + $in['rate']*$remq;
				break;
			}else{
				$remq = $remq - $in['qty'];
				$amount = $amount + ($in['rate']*$in['qty']);
			}
		}
		$rate = ($product->remaining_stock > 0)?$amount/$product->remaining_stock:0;
        return [
            $product->code,
            $product->name,
            $remaining,
			$rate,
			$amount
        ];
    }
}
