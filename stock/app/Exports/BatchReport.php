<?php

namespace App\Exports;

use App\BatchProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BatchReport implements FromCollection, WithHeadings
{
    protected $batchNo;

    public function __construct($batchNo)
    {
        $this->batchNo = $batchNo;
    }

    public function collection()
    {
        $counter = 0;

        $query = BatchProduct::with(['batch', 'product']) 
            ->when($this->batchNo != 'all', function ($q) {
                $q->whereHas('batch', function ($qb) {
                    $qb->where('batch_no', $this->batchNo);
                });
            });

        return $query->get()->map(function ($bp) use (&$counter) {
            $counter++;
            return [
                'id' => $counter,
                'batch_no' => $bp->batch->batch_no ?? '',
                'product_sku' => $bp->product->code ?? '',
                'product_name' => $bp->product->name ?? '', 
                'supp_in_no' => $bp->supp_in_no ?? '', 
                'quantity' => $bp->quantity,
                
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Batch No',
            'Product Code',
            'Product Name',
            'Supplier Invoice No',
            'Quantity',
          
        ];
    }
}
