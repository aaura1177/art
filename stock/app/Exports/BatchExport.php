<?php

namespace App\Exports;

use App\stockLog;
use App\product; // Make sure your Product model points to product_table
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class BatchExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $logsToExport = collect();

        $products = product::where('quantity', '>', 0)->get();

        foreach ($products as $product) {

            $logs = stockLog::where('product_id', $product->id)
                ->whereNotNull('batch_no')
                ->where('type', 1)
                ->whereDate('created_at', '>=', '2025-04-01')
                ->orderByDesc('created_at')
                ->get();

            $qtyNeeded = $product->quantity;
            foreach ($logs as $log) {
                if ($qtyNeeded <= 0) break;

                $takeQty = min($log->quantity, $qtyNeeded);

                $logsToExport->push([
                    'supplier_name' => $log->supplier_name ?? ($log->product->supplier->c_name ?? ''),
                    'batch_no'      => $log->batch_no,
                    'product_id'    => $log->product_id,
                    'quantity'      => $takeQty,
                    'voucher_no'    => $log->voucher_no,
                    'date'          => optional($log->created_at)->format('Y-m-d'),
                ]);

                $qtyNeeded -= $takeQty;
            }
        }

        return $logsToExport;
    }

    public function headings(): array
    {
        return [
            'supplier_name',
            'batch_no',
            'product_id',
            'quantity',
            'voucher_no',
            'date',
        ];
    }
}
