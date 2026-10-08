<?php

namespace App\Exports;

use App\WholesaleShipment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WholesaleNegativeListExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shipmentId;

    public function __construct($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    public function collection()
    {
        $shipment = WholesaleShipment::with([
            'allocations.supplier',
            'allocations.product',
            'allocations.purchaseOrder.poTable',
        ])->findOrFail($this->shipmentId);

        $rows = collect();

        foreach ($shipment->allocations->where('status', 'po_generated') as $alloc) {
            $po = $alloc->purchaseOrder;
            $poLine = $po ? $po->poTable->firstWhere('product_id', $alloc->product_id) : null;
            $remQty = $poLine ? (int) $poLine->remqty : (int) $alloc->asked_quantity;
            if ($remQty <= 0) {
                continue;
            }
            $asked = (int) $alloc->asked_quantity;
            $rows->push([
                'product_name' => $alloc->product->name ?? '',
                'sku' => $alloc->product_sku,
                'supplier' => $alloc->supplier->c_name ?? '',
                'po_no' => $po->pono ?? '',
                'po_date' => $po && $po->podate ? date('d M Y', strtotime($po->podate)) : '',
                'delivery_date' => $po && $po->del_date ? date('d M Y', strtotime($po->del_date)) : '',
                'asked_qty' => $asked,
                'delivered_qty' => max(0, $asked - $remQty),
                'rem_qty' => $remQty,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Supplier Name',
            'PO No',
            'Purchase Order Date',
            'Delivery Date',
            'Asked Qty',
            'Delivered Qty',
            'Remaining Qty',
        ];
    }

    public function map($row): array
    {
        return [
            $row['product_name'],
            $row['sku'],
            $row['supplier'],
            $row['po_no'],
            $row['po_date'],
            $row['delivery_date'],
            $row['asked_qty'],
            $row['delivered_qty'],
            $row['rem_qty'],
        ];
    }
}
