<?php

namespace App\Exports;

use App\Batch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BatchLogExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $counter = 0; 

        return Batch::with('supplier')->get()->map(function ($batch) use (&$counter) {
            $counter++; 
            return [
                'id' => $counter, 
                'batch_no' => $batch->batch_no,
                'supplier_name' => $batch->supplier->c_name ?? '', 
                'quantity' => $batch->quantity,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Batch No',
            'Supplier Name',
            'Quantity',
        ];
    }
}
