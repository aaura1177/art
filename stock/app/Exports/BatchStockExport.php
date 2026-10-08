<?php

namespace App\Exports;

use App\Batch;
use App\BatchProduct;
use App\stockLog;
use App\product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BatchStockExport implements FromCollection, WithHeadings, WithMapping
{
    protected $batchNo;
    protected $fromDate;
    protected $toDate;

    public function __construct($batchNo, $fromDate, $toDate)
    {
        $this->batchNo = $batchNo;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $query = stockLog::query()
            ->whereNotNull('batch_no')
            ->where('batch_no', '!=', ''); // ✅ only those having batch_no

        if ($this->batchNo && $this->batchNo !== 'all') {
            $query->where('batch_no', $this->batchNo);
        }

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59'
            ]);
        }

        $stockLogs = $query->orderBy('batch_no')
            ->orderBy('product_id')
            ->orderBy('created_at')
            ->get();

        $grouped = $stockLogs->groupBy(fn($item) => $item->batch_no . '-' . $item->product_id);

        $finalData = collect();

        foreach ($grouped as $logs) {
            $firstLog = $logs->first();
            $batch = Batch::where('batch_no', $firstLog->batch_no)->first();
            $product = product::find($firstLog->product_id);

            $totalInQty = $logs->where('type', 1)->sum('quantity');
            $totalOutQty = $logs->where('type', 2)->sum('quantity');

            $openingStock = 0;

            if ($batch) {
                $batchProduct = BatchProduct::where('batch_id', $batch->id)
                    ->where('product_id', $firstLog->product_id)
                    ->first();

                if ($batchProduct) {
                    $openingStock = ($batchProduct->quantity + $totalOutQty) - $totalInQty;
                }
            }

            $openingStock = $openingStock ?? 0;

            foreach ($logs as $log) {
                $inQty = $log->type == 1 ? $log->quantity : 0;
                $outQty = $log->type == 2 ? $log->quantity : 0;
                $closingStock = $openingStock + $inQty - $outQty;

                $finalData->push([
                    'date' => optional($log->created_at)->format('d F Y'),
                    'sku' => $product ? $product->code : 'Unknown',
                    'batch_no' => $log->batch_no,
                    'supplier_name' => $log->supplier_name ?? 'Unknown',
'buyer_name' => $log->buyer_name ?? '',
                    'invoice_no' => $log->supplier_inv_no ?? 'Unknown',
                    'opening_stock' => (float)$openingStock,
                    'stock_in' => (float)$inQty,
                    'stock_out' => (float)$outQty,
                    'closing_stock' => (float)$closingStock,
                ]);

                $openingStock = $closingStock;
            }
        }

        return $finalData;
    }

    public function headings(): array
    {
        return [
            'Date',
            'SKU',
            'Batch No',
            'Supplier Name',
'Buyer Name',
            'Invoice No',
            'Opening Stock',
            'Stock In',
            'Stock Out',
            'Closing Stock',
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['sku'],
            $row['batch_no'],
            $row['supplier_name'],
$row['buyer_name'],
            $row['invoice_no'],
            number_format($row['opening_stock'], 2),
            number_format($row['stock_in'], 2),
            number_format($row['stock_out'], 2),
            number_format($row['closing_stock'], 2),
        ];
    }
}