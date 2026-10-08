<?php

namespace App\Exports;
use App\stockLog;
use App\supplierInvoice;
use App\poTable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class StockReportProduct implements FromCollection, WithHeadings
{
      protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = stockLog::query();

        if (!empty($this->filters['from_date']) && !empty($this->filters['to_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['from_date'] . ' 00:00:00',
                $this->filters['to_date'] . ' 23:59:59'
            ]);
        }

        if (!empty($this->filters['invoice_no'])) {
            $query->where('voucher_no', $this->filters['invoice_no']);
        }
        if (!empty($this->filters['product_id'])) {
            $query->where('product_id', $this->filters['product_id']);
        }

        if (!empty($this->filters['batch_no'])) {
            $query->where('batch_no', $this->filters['batch_no']);
        }

        $counter = 0;

        return $query->get()->map(function ($log) use (&$counter) {
            $counter++;

            $supplierInvoice = supplierInvoice::where('batch_no', $log->batch_no)->where('supplier_invoice_number',$log->voucher_no)->first();
            $poTable = $supplierInvoice
                ? poTable::where('poid', $supplierInvoice->purchase_order_id)
                    ->where('product_id', $log->product_id)
                    ->first()
                : null;

            return [
                'ID' => $counter,
                'Date' => \Carbon\Carbon::parse($log->created_at)->format('d M Y'),
                'Voucher' => $log->supplier_inv_no ?? '',
                'SKU' => $log->product->code ?? '',
                'Description' => $log->product?->code . ' (' . $log->product?->name . ')' ?? '',

                'Rate' => $poTable->rate ?? '',
                'Invoice No' => $log->voucher_no ?? '',
                'Opening Balance' => $log->opening_balance ?? 0,
                'IN' => $log->type == 1 ? $log->quantity : '',
                'OUT' => $log->type == 2 ? $log->quantity : '',
                'Close Balance' => $log->remaining_stock ?? 0,
                'Batch NO' => $log->batch_no ?? '',
            ];
        }); 
    } 

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Invoice No',
            'SKU',
            'Description',
            'Rate',
            'Voucher',
            'Opening Balance',
            'IN',
            'OUT',
            'Close Balance',
            'Batch NO',
        ];
    }
}
