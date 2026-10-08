<?php

namespace App\Imports;

use App\Batch;
use App\BatchProduct;
use App\supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class BatchImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $supplier = supplier::where('c_name', $row['supplier_name'])->first();
        if (!$supplier) {
            return null;
        }
$cleanBatchNo = substr($row['batch_no'], 0, -4);
        $batch = Batch::whereRaw("LEFT(batch_no, LENGTH(batch_no) - 4) = ?", $cleanBatchNo)->first();
        if ($batch) {
            $batch->quantity += $row['quantity'] ?? 0;
            $batch->save();
        } else {
            $batch = Batch::create([
                'supplier_id' => $supplier->id,
                'batch_no'    => $row['batch_no'],
                'quantity'    => $row['quantity'] ?? 0,
                'date'        => isset($row['date']) ? date('Y-m-d', strtotime($row['date'])) : now()->format('Y-m-d'),
            ]);
        }

        $batchProduct = BatchProduct::firstOrNew([
            'batch_id'   => $batch->id,
            'product_id' => $row['product_id'],
        ]);

        $batchProduct->quantity = ($batchProduct->quantity ?? 0) + ($row['quantity'] ?? 0);
        $batchProduct->supp_in_no = $row['voucher_no'] ?? $batchProduct->supp_in_no;
        $batchProduct->save();
    }
}
