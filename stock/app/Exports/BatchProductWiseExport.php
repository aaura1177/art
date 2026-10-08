<?php

namespace App\Exports;

use App\BatchProduct;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BatchProductWiseExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return BatchProduct::join('product_table', 'batch_product.product_id', '=', 'product_table.id')
            ->select(
                'product_table.code as product_code',
                DB::raw('SUM(batch_product.quantity) as total_batch_quantity'),
                'product_table.quantity as product_quantity'
            )
            ->groupBy('product_table.id', 'product_table.code', 'product_table.quantity')
            ->orderBy('product_table.code', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Total Batch Quantity',
            'Product Quantity ',
        ];
    }
}
