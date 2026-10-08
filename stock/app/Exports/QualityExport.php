<?php

namespace App\Exports;

use App\quality;
use App\channel;
use App\product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class QualityExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return quality::with('product', 'channel')
            ->get()
            ->map(function ($q) {
                return [
                    'Id'           => $q->id,
                    'Product Sku' => $q->product ? $q->product->code : '',
                    'Product Name' => $q->product ? $q->product->name : '',
                    'channel' => $q->channel ? $q->channel->name : '',
                    'Artisan Order No.' => $q->supplier_inv_no,
                    'Quantity'   => $q->quantity,
                    'Date'   => \Carbon\Carbon::parse($q->date)->format('d M, Y'),
                    'remarks'   => $q->remarks,
                    
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product SKU',
            'Product Name',
            'Channel',
            'Artisan Order No.',
            'Quantity',
            'Date',
            'Remarks',
            
        ];
    }
}
