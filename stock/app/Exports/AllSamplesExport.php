<?php

namespace App\Exports;

use App\sample; 
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllSamplesExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct()
    {
        //
    }

    public function collection()
    {
        $product = sample::all();
        return $product;
    }

    
    public function headings(): array
    {
        return [
            'Product',
            'Code ',
            'Product code',
            'Product Name',
            'EAN',
            'HSN',
            'Category Name',
            'Subcategory Name',
            'GSTSlab',
            'Quantity',
            'Finishing',
            'Width',
            'Height',
            'Depth',
            'Box Width',
            'Box Height',
            'Box Depth',
            'Wholesale Volume',
            'Dropship Volume',
            'Volume',
            'Hardware 1',
            'Hardware 2',
            'Hardware 3',
            'Hardware 4',
            'Hardware 5',
            'Hardware 1 Quantity',
            'Hardware 2 Quantity',
            'Hardware 3 Quantity',
            'Hardware 4 Quantity',
            'Hardware 5 Quantity',
            'Upholstry',
            'Corner',
            'L Hardware',
            'Addons',
            'Remarks',
            'date',
            'updated',
            
        ];
    }

    
    public function map($product): array
    {
        return [
            $product->code. ' - ' .$product->name,
            $product->code,
            $product->prod_code,
            $product->name,
            $product->ean,
            $product->hsn,
            $product->category->name,
            $product->subcategory->name,
            $product->gstslab,
            $product->quantity,
            $product->finishing,
            $product->width,
            $product->height,
            $product->depth,
            $product->boxwidth,
            $product->boxheight,
            $product->boxdepth,
            $product->wholesalevolume,
            $product->dropshipvolume,
            $product->volume,
            $product->hardware1,
            $product->hardware2,
            $product->hardware3,
            $product->hardware4,
            $product->hardware5,
            $product->hardware1_quantity,
            $product->hardware2_quantity,
            $product->hardware3_quantity,
            $product->hardware4_quantity,
            $product->hardware5_quantity,
            $product->upholstry,
            $product->corner,
            $product->lhardware,
            $product->addons,
            $product->remarks,
            Carbon::parse($product->created_at)->format('Y-m-d'),
            Carbon::parse($product->updated_at)->format('Y-m-d'),
            
        ];
    }
}
